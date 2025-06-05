<?php

namespace App\Http\Controllers;

use App\Models\HistoricoPag;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Exibe estatísticas de pagamento do usuário logado.
     *
     * @OA\Get(
     *     path="/api/estatisticas-pagamentos",
     *     summary="Listar estatísticas de pagamentos do usuário logado",
     *     tags={"Dashboard", "Pagamentos"},
     *     security={{"BearerAuth":{}}}, 
     *     @OA\Response(
     *         response=200,
     *         description="Estatísticas de pagamentos do usuário logado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="categorias_pagas", type="array", @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="categoria", type="string", example="Categoria A"),
     *                 @OA\Property(property="total", type="integer", example=3)
     *             )),
     *             @OA\Property(property="total_diario_pago", type="number", format="float", example=1500.50),
     *             @OA\Property(property="total_custo_diario", type="number", format="float", example=800.25),
     *             @OA\Property(property="total_lucro_diario", type="number", format="float", example=700.25)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autorizado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Não autorizado")
     *         )
     *     )
     * )
     */
    public function estatisticasPagamentos(Request $request)
    {
        try {
            // Obtém o usuário logado
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Usuário não autorizado'], 401);
            }

            // Obtém os clientes associados ao usuário logado (baseado no user_id)
            $clientes = Cliente::where('user_id', $user->id)->get();

            // Verifica se o usuário tem clientes associados
            if ($clientes->isEmpty()) {
                return response()->json(['error' => 'Nenhum cliente encontrado para o usuário'], 404);
            }

            // Contagem de categorias para pagamentos pagos
            $categoriasPagas = HistoricoPag::whereIn('cliente_id', $clientes->pluck('id'))
                ->whereBetween('created_at', [
                    $request->input('data_inicio', Carbon::now()->startOfDay()),
                    $request->input('data_fim', Carbon::now()->endOfDay())
                ])
                ->where('status', 'pago')
                ->where('categoria_name', '!=', '') // Filtro para evitar categorias vazias
                ->get()
                ->pluck('categoria_name')
                ->countBy()
                ->map(function($count, $categoria) {
                    return ['categoria' => $categoria, 'total' => $count];
                })
                ->sortByDesc('total')
                ->values()
                ->toArray();

            // Total diário de pagamentos pagos
            $totalDiarioPago = HistoricoPag::whereIn('cliente_id', $clientes->pluck('id'))
                ->whereDate('created_at', Carbon::today())
                ->where('status', 'pago')
                ->sum('valor');

            // Total diário de custo (baseado na coluna 'custo' da tabela HistoricoPag)
            $totalCustoDiario = HistoricoPag::whereIn('cliente_id', $clientes->pluck('id'))
                ->whereDate('created_at', Carbon::today())
                ->where('status', 'pago')
                ->sum('custo');

            // Total diário de lucro (total pago diário - total custo diário)
            $totalLucroDiario = $totalDiarioPago - $totalCustoDiario;

            // Retorna as estatísticas
            return response()->json([
                'categorias_pagas' => $categoriasPagas,
                'total_diario_pago' => $totalDiarioPago,
                'total_custo_diario' => $totalCustoDiario,
                'total_lucro_diario' => $totalLucroDiario
            ]);

        } catch (\Exception $e) {
            // Loga o erro para debug
            Log::error('Erro ao listar estatísticas de pagamentos: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao listar estatísticas de pagamentos'], 500);
        }
    }
}