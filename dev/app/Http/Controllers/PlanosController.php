<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Instancia;
use Illuminate\Support\Facades\Http;


class PlanosController extends Controller
{
    /**
     * Retorna os dados do plano atual do usuário autenticado.
     */
    public function meuPlano(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }

        $planId = $user->plan_id;
        $dataInicio = $user->data_inicio;
        $dataFim = $user->data_fim;

        $plano = Plan::find($planId);

        // Monta o array de resposta
        $retorno = [
            'plan_id' => $planId,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
            'plan' => null,
        ];

        if ($plano) {
            $retorno['plan'] = [
                'nome' => $plano->nome,
                'descricao' => $plano->descricao,
                'preco' => $plano->preco,
                'duracao_dias' => $plano->duracao_dias,
                'tipo' => $plano->tipo,
            ];
        }

        return response()->json($retorno);
    }


   public function checkExpiredPlans()
{
    $today = Carbon::today();
    $apiToken = "asdfghjksjhggbnmfdfghjhgf";

    // Procura usuários com planos ativos cuja data_fim expirou
    $users = User::whereIn('plan_id', [2, 3, 4, 5])
                ->whereDate('data_fim', '<', $today)
                ->get();

    $usuariosAtualizados = 0;
    $instanciasDeletadas = 0;

    foreach ($users as $user) {
        // Atualiza plano do usuário
        $user->plan_id = 1;
        $user->save();
        $usuariosAtualizados++;

        // Busca todas as instâncias vinculadas a este usuário
        $instancias = Instancia::where('user_id', $user->id)->get();

        foreach ($instancias as $instancia) {
            $sessionId = $instancia->name;

            // Tenta deletar sessão via API externa
            $response = Http::withHeaders([
                'x-api-key' => $apiToken
            ])->get("https://dapleserver.com/session/terminate/{$sessionId}");

            if ($response->successful()) {
                // Remove a instância do banco
                $instancia->delete();
                $instanciasDeletadas++;
            }
        }
    }

    return response()->json([
        'message' => "Planos expirados tratados com sucesso.",
        'usuarios_atualizados' => $usuariosAtualizados,
        'instancias_removidas' => $instanciasDeletadas
    ]);

}
}
