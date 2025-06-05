<?php



namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ResellerController extends Controller
{
    // Método para obter as informações do revendedor autenticado
    public function getResellerInfo(Request $request)
    {
        $user = Auth::user();

        if ($user) {
            $urlPadrao = env('APP_URL');
            $codigoRevendedor = $user->codigo_revendedor;

            $urlIndicacao = $urlPadrao  . $codigoRevendedor;

            $resellerInfo = [
                'name' => $user->name,
                'email' => $user->email,
                'codigo_revendedor' => $codigoRevendedor,
                'porcent_resseller' => $user->porcent_resseller,
                'credito' => $user->credito,
                'url_indicacao' => $urlIndicacao,
            ];

            return response()->json($resellerInfo);
        } else {
            return response()->json(['error' => 'Usuário não autenticado'], 401);
        }
    }



     // Método para contar clientes indicados pelo revendedor autenticado
     public function contarClientesIndicados(Request $request)
     {
         // Verifica se o usuário está autenticado
         $user = Auth::user();
 
         if (!$user) {
             return response()->json(['error' => 'Usuário não autenticado'], 401);
         }
 
         // Pega o código do revendedor do usuário autenticado
         $codigoRevendedor = $user->codigo_revendedor;
 
         if (!$codigoRevendedor) {
             return response()->json(['error' => 'Usuário não possui código de revendedor'], 404);
         }
 
         // Conta o total de usuários que possuem o código de indicação igual ao código do revendedor
         $totalClientes = User::where('codigo_indicacao', $codigoRevendedor)->count();
 
         // Conta quantos dos indicados têm plan_id entre 2 e 5
         $clientesComPlanoPago = User::where('codigo_indicacao', $codigoRevendedor)
                                    ->whereBetween('plan_id', [2, 5])
                                    ->count();
 
         // Retorna os resultados em formato JSON
         return response()->json([
             'success' => true,
             'total_clientes_indicados' => $totalClientes,
             'clientes_com_plano_pago' => $clientesComPlanoPago,
             'codigo_revendedor' => $codigoRevendedor
         ]);
     }

    // app/Http/Controllers/UserController.php
public function updateChavePix(Request $request)
{
    // Lógica para atualizar a chave Pix
    $request->user()->update(['chave_pix' => $request->input('chave_pix')]);

    return back()->with('success', 'Chave Pix atualizada com sucesso!');
}
}