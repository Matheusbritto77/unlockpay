<?php

namespace App\Http\Controllers;

use App\Models\ChatP2P;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class ChatP2PController extends Controller
{
    /**
     * Retorna uma lista paginada de peers com os usuários relacionados.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $peers = ChatP2P::with('user')->paginate(300);

        return response()->json([
            'message' => 'Peers recuperados com sucesso.',
            'data'    => $peers,
        ], 200);
    }
    
    
    
    
    
    
    
    
    
    

   /**
 * Armazena ou atualiza um peer vinculado ao usuário autenticado.
 * Impede duplicação de IP e porta em usuários diferentes.
 *
 * @param  Request  $request
 * @return JsonResponse
 */
public function store(Request $request): JsonResponse
{
    // Obter o usuário autenticado
    $user = $request->user();

    // Log de entrada da requisição
    Log::info('[ChatP2P] Requisição recebida para salvar peer.', [
        'user_id' => $user?->id,
        'ip'      => $request->input('ip'),
        'port'    => $request->input('port'),
        'payload' => $request->all(),
    ]);

    // Validação dos dados
    $validator = Validator::make($request->all(), [
        'ip'   => 'required|ip',
        'port' => 'required|integer|min:1|max:65535',
    ]);

    if ($validator->fails()) {
        Log::warning('[ChatP2P] Validação falhou.', [
            'user_id' => $user?->id,
            'errors'  => $validator->errors()->toArray(),
            'input'   => $request->all(),
        ]);

        return response()->json([
            'message' => 'Dados inválidos.',
            'errors'  => $validator->errors(),
        ], 422);
    }

    $ip   = $request->input('ip');
    $port = $request->input('port');

    // Verifica se já existe um peer com esse IP e porta em outro usuário
    $existingPeer = ChatP2P::where('ip', $ip)
        ->where('port', $port)
        ->where('user_id', '!=', $user->id)
        ->first();

    if ($existingPeer) {
        Log::info('[ChatP2P] Conflito de IP e porta detectado.', [
            'user_id'       => $user->id,
            'conflict_user' => $existingPeer->user_id,
            'ip'            => $ip,
            'port'          => $port,
        ]);

        return response()->json([
            'message' => 'Endereço IP e porta já estão em uso por outro usuário.',
        ], 409);
    }

    try {
        // Cria ou atualiza o peer do usuário
        $peer = ChatP2P::updateOrCreate(
            ['user_id' => $user->id],
            [
                'ip'        => $ip,
                'port'      => $port,
                'last_seen' => now(),
            ]
        );

        Log::info('[ChatP2P] Peer salvo/atualizado com sucesso.', [
            'user_id' => $user->id,
            'peer_id' => $peer->id,
            'ip'      => $ip,
            'port'    => $port,
        ]);

        return response()->json([
            'message' => 'Peer salvo com sucesso.',
            'data'    => $peer,
        ], 200);

    } catch (\Throwable $e) {
        Log::error('[ChatP2P] Erro ao salvar peer.', [
            'user_id' => $user->id,
            'ip'      => $ip,
            'port'    => $port,
            'error'   => $e->getMessage(),
            'trace'   => $e->getTraceAsString(),
        ]);

        return response()->json([
            'message' => 'Erro interno ao salvar peer.',
        ], 500);
    }
}

}
