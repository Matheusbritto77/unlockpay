<?php

namespace App\Http\Controllers;

use App\Models\HistoricoPag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class ResumeController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/resumo-pagamentos",
     *     summary="Resumo de pagamentos por período (relacionado ao usuário autenticado)",
     *     tags={"Pagamentos"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="data_inicio",
     *         in="query",
     *         required=true,
     *         description="Data de início no formato Y-m-d",
     *         @OA\Schema(type="string", format="date", example="2025-03-01")
     *     ),
     *     @OA\Parameter(
     *         name="data_fim",
     *         in="query",
     *         required=true,
     *         description="Data de fim no formato Y-m-d",
     *         @OA\Schema(type="string", format="date", example="2025-03-31")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Resumo de pagamentos por dia do usuário autenticado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="resumo_diario", type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="data", type="string", example="2025-03-15"),
     *                     @OA\Property(property="pago", type="integer", example=5),
     *                     @OA\Property(property="nao_pago", type="integer", example=3),
     *                     @OA\Property(property="valor_pago", type="number", format="float", example=950.00),
     *                     @OA\Property(property="valor_nao_pago", type="number", format="float", example=300.50),
     *                     @OA\Property(property="total_valor", type="number", format="float", example=1250.50),
     *                     @OA\Property(property="total_valor_pagos", type="number", format="float", example=950.00),
     *                     @OA\Property(property="total_valor_nao_pagos", type="number", format="float", example=300.50),
     *                     @OA\Property(property="total_valor_lucro", type="number", format="float", example=649.50),
     *                     @OA\Property(property="custo_total", type="number", format="float", example=200.00)
     *                 )
     *             ),
     *             @OA\Property(property="total_periodo", type="object",
     *                 @OA\Property(property="total_valor_pago", type="number", format="float", example=5000.00),
     *                 @OA\Property(property="total_valor_nao_pago", type="number", format="float", example=1500.00),
     *                 @OA\Property(property="total_custo", type="number", format="float", example=2000.00)
     *             ),
     *             @OA\Property(property="categorias", type="object",
     *                 @OA\Property(property="pagas", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="categoria", type="string", example="mdm"),
     *                         @OA\Property(property="total", type="integer", example=5)
     *                     )
     *                 ),
     *                 @OA\Property(property="nao_pagas", type="array",
     *                     @OA\Items(
     *                         @OA\Property(property="categoria", type="string", example="mdm"),
     *                         @OA\Property(property="total", type="integer", example=3)
     *                     )
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function getResumoPorPeriodo(Request $request)
    {
        // Validação dos parâmetros da requisição
        $validatedData = $request->validate([
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
        ]);

        // Verificação de autenticação do usuário
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Não autorizado'], 401);
        }

        // Recuperação dos IDs dos clientes do usuário
        $clientesIds = \App\Models\Cliente::where('user_id', $user->id)->pluck('id');

        // Busca e agrupamento dos pagamentos
        $pagamentos = HistoricoPag::whereIn('cliente_id', $clientesIds)
            ->whereBetween('created_at', [
                $validatedData['data_inicio'] . ' 00:00:00',
                $validatedData['data_fim'] . ' 23:59:59'
            ])
            ->get()
            ->groupBy(function ($item) {
                return $item->created_at->format('Y-m-d');
            });

        // Inicialização das variáveis de resumo
        $resumo = [];
        $totalValorPagoPeriodo = 0;
        $totalValorNaoPagoPeriodo = 0;
        $totalCustoPeriodo = 0;

        // Contagem de categorias para pagamentos pagos
        $categoriasPagas = HistoricoPag::whereIn('cliente_id', $clientesIds)
            ->whereBetween('created_at', [
                $validatedData['data_inicio'] . ' 00:00:00',
                $validatedData['data_fim'] . ' 23:59:59'
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

        // Contagem de categorias para pagamentos não pagos
        $categoriasNaoPagas = HistoricoPag::whereIn('cliente_id', $clientesIds)
            ->whereBetween('created_at', [
                $validatedData['data_inicio'] . ' 00:00:00',
                $validatedData['data_fim'] . ' 23:59:59'
            ])
            ->where('status', 'nao_pago')
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

        // Processamento dos pagamentos por data
        foreach ($pagamentos as $data => $itens) {
            $pago = $itens->where('status', 'pago')->count();
            $naoPago = $itens->where('status', 'nao_pago')->count();
            $custoCount = $itens->where('custo', '>', 0)->count();

            $valorPago = $itens->where('status', 'pago')->sum('valor');
            $valorNaoPago = $itens->where('status', 'nao_pago')->sum('valor');
            $valorCusto = $itens->sum('custo');

            $totalValorPagos = $valorPago;
            $totalValorNaoPagos = $valorNaoPago;
            $totalValorLucro = $valorPago - $valorCusto;
            $totalValorCusto = $valorCusto;

            $resumo[] = [
                'data' => $data,
                'pago' => $pago,
                'nao_pago' => $naoPago,
                'custo' => $custoCount,
                'valor_pago' => $valorPago,
                'valor_nao_pago' => $valorNaoPago,
                'valor_custo' => $valorCusto,
                'total_valor_pagos' => $totalValorPagos,
                'total_valor_nao_pagos' => $totalValorNaoPagos,
                'total_valor_lucro' => $totalValorLucro,
                'total_valor_custo' => $totalValorCusto
            ];

            $totalValorPagoPeriodo += $valorPago;
            $totalValorNaoPagoPeriodo += $valorNaoPago;
            $totalCustoPeriodo += $valorCusto;
        }

        // Preparação da resposta
        $response = [
            'resumo_diario' => $resumo,
            'total_periodo' => [
                'total_valor_pago' => $totalValorPagoPeriodo,
                'total_valor_nao_pago' => $totalValorNaoPagoPeriodo,
                'total_custo' => $totalCustoPeriodo
            ],
            'categorias' => [
                'pagas' => $categoriasPagas,
                'nao_pagas' => $categoriasNaoPagas
            ]
        ];

        // Retorno da resposta em formato JSON
        return response()->json($response);
    }
}