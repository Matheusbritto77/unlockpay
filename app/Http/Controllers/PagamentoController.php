<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; // Para manipular datas
use Illuminate\Support\Facades\Auth; // Para obter o usuário logado
use Illuminate\Support\Facades\Log;
use App\Models\Pagamento;
use App\Models\Plan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\User;

/**
 * PagamentoController
 * 
 * Controller responsável por gerenciar os pagamentos e suas respectivas informações.
 */
class PagamentoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/pagamentos/ultimas-24h",
     *     summary="Recupera pagamentos pendentes das últimas 24 horas",
     *     description="Esta rota retorna os pagamentos com status 'nao_paga' das últimas 24 horas.",
     *     tags={"Pagamentos"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Retorna os pagamentos pendentes com sucesso",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", description="ID do pagamento"),
     *                 @OA\Property(property="referencepix", type="string", description="Referencia do Pix"),
     *                 @OA\Property(property="status", type="string", description="Status do pagamento"),
     *                 @OA\Property(property="cliente_id", type="integer", description="ID do cliente"),
     *                 @OA\Property(property="reference_id", type="string", description="ID de referência")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nenhum pagamento encontrado",
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor",
     *     )
     * )
     */
    public function index(Request $request)
    {
        // Pega o usuário logado
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Usuário não autorizado'], 401);
        }

        // Calcula a data e hora de 24 horas atrás
        $data24HorasAtras = Carbon::now()->subDay();

        // Consulta os pagamentos que pertencem ao cliente do usuário logado
        $pagamentos = DB::table('historicopag')
            ->join('clientes', 'historicopag.cliente_id', '=', 'clientes.id')
            ->where('clientes.user_id', $user->id) // Filtra pelos clientes do usuário logado
            ->where('historicopag.status', 'nao_pago')
            ->where('historicopag.updated_at', '>=', $data24HorasAtras)
            ->select('historicopag.id', 'historicopag.status', 'historicopag.cliente_id', 'historicopag.reference_id')
            ->get();

        // Se não encontrar pagamentos, retorna 404
        if ($pagamentos->isEmpty()) {
            return response()->json(['message' => 'Nenhum pagamento encontrado'], 404);
        }

        // Retorna os resultados como JSON
        return response()->json($pagamentos);
    }




    
    /**
 * @OA\Post(
 *     path="/api/pagamentos/atualizar-status",
 *     summary="Atualiza o status de pagamento para 'pago'",
 *     description="Esta rota recebe o 'reference_id' e o 'status' de um pagamento e atualiza o status na tabela 'historicopag'.",
 *     tags={"Pagamentos"},
 *     security={{"BearerAuth":{}}},
 *     @OA\Parameter(
 *         name="reference_id",
 *         in="query",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="ID de referência do pagamento"
 *     ),
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         required=true,
 *         @OA\Schema(type="string"),
 *         description="Novo status do pagamento"
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Status do pagamento atualizado com sucesso",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", description="Mensagem de sucesso"),
 *             @OA\Property(property="numero", type="string", description="Número do pagamento atualizado"),
 *             @OA\Property(property="nome", type="string", description="Nome do cliente do pagamento atualizado"),
 *             @OA\Property(property="instancia", type="string", description="Instância do pagamento atualizado"),
 *             @OA\Property(property="valor", type="string", description="Valor do pagamento atualizado"),
 *             @OA\Property(property="descricao", type="string", description="Descrição do pagamento atualizado"),
 *             @OA\Property(property="dataHoraCriacao", type="string", description="Data e hora de criação do pagamento")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Pagamento não encontrado",
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Dados inválidos ou não informados",
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erro interno do servidor",
 *     )
 * )
 */
public function atualizarStatus(Request $request)
{
    // Valida os parâmetros de entrada
    $validated = $request->validate([
        'reference_id' => 'required|string',
        'status' => 'required|string',
    ]);

    // Recebe os dados do request
    $referenceId = $validated['reference_id'];
    $status = $validated['status'];

    // Busca o pagamento na tabela historicopag com o reference_id
    $pagamento = DB::table('historicopag')
        ->where('reference_id', $referenceId)
        ->where('status', 'nao_pago')
        ->first();

    // Se o pagamento não for encontrado, retorna 404
    if (!$pagamento) {
        return response()->json(['message' => 'Pagamento não encontrado'], 404);
    }

    // Atualiza o status do pagamento para o novo status
    DB::table('historicopag')
        ->where('reference_id', $referenceId)
        ->update(['status' => $status, 'updated_at' => now()]);

    // Registra o sucesso e retorna uma resposta JSON com as informações do pagamento atualizado
    Log::info("Pagamento com reference_id {$referenceId} atualizado para status '{$status}'.");

    return response()->json([
        'message' => 'Status do pagamento atualizado com sucesso',
        'numero' => $pagamento->numero,
        'nome' => $pagamento->nome,
        'instancia' => $pagamento->instancia,
        'valor' => $pagamento->valor, // Valor do pagamento
        'descricao' => $pagamento->descricao, // Descrição do pagamento
        'dataHoraCriacao' => $pagamento->created_at, // Data e hora de criação do pagamento
        'ID' => $referenceId, // Data e hora de criação do pagamento
    ], 200);
}






    public function criarPagamentoComQrCode($plan_id)
    {
        // Encontra o plano
        $plan = Plan::find($plan_id);
        if (!$plan) {
            return response()->json(['message' => 'Plano não encontrado'], 404);
        }

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Usuário não autenticado'], 401);
        }

        // Cabeçalhos para a requisição ao Mercado Pago
        $headers = [
            'Authorization' => 'Bearer 310-18324d02b21a0cc59c217cf71be261fa-229395063',
            'Content-Type' => 'application/json',
            'X-Idempotency-Key' => Str::uuid()->toString(),
        ];

        // Dados para a requisição
$paymentData = [
    'transaction_amount' => (float) $plan->preco,
    'description' => $plan->nome ?? 'Pagamento via QR Code',
    'payment_method_id' => 'pix',
    'payer' => [
        'email' => $user->email,
        'first_name' => $user->name,
        'last_name' => '',
    ],
    'notification_url' => 'https://unlockpay.com.br/api/mercadopago/callback', // <-- ajuste aqui
];

        // Criação do pagamento via Mercado Pago
        $response = Http::withHeaders($headers)->post(
            'https://api.mercadopago.com/v1/payments',
            $paymentData
        );

        if ($response->failed()) {
            return response()->json(['message' => 'Erro ao criar pagamento: ' . $response->body()], 500);
        }

        // Extrai dados da resposta
        $qrCode = $response->json('point_of_interaction.transaction_data.qr_code');
        $qrCodeBase64 = $response->json('point_of_interaction.transaction_data.qr_code_base64');
        $referenceId = $response->json('id');

        // Salva informação na tabela pagamentos
        $pagamento = Pagamento::create([
            'user_id'   => $user->id,
            'reference' => $referenceId,
            'status'    => 1,
            'valor' => $plan->preco,
            'plan_id' => $plan_id,
        ]);

        // Retorna JSON com os qr_codes
        return response()->json([
            'qr_code'         => $qrCode,
            'qr_code_base64'  => $qrCodeBase64,
            'reference'    => $referenceId,
            'pagamento_id'    => $pagamento->id
        ]);
    }


    public function callback(Request $request)
{
    \Log::info('Webhook recebido do Mercado Pago:', ['payload' => $request->all()]);

    // 1. Pega o ID de pagamento vindo da notificação
    $mpId = null;
    if ($request->has('data.id')) {
        $mpId = $request->input('data.id');
    } elseif ($request->has('id')) {
        $mpId = $request->input('id');
    } elseif ($request->has('reference_id')) {
        $mpId = $request->input('reference_id');
    }

    if (!$mpId) {
        \Log::warning('Webhook: ID do pagamento não encontrado.', ['body' => $request->all()]);
        return response()->json(['message' => 'ID do pagamento não enviado.'], 400);
    }

    // 2. Consulta o Mercado Pago para conferir status real do pagamento
    $accessToken = 'APP_USR--040310-18324d02b21a0cc59c217cf71be261fa-229395063'; // Troque pelo seu token correto/gerencie via .env

    $client = new \GuzzleHttp\Client([
        'base_uri' => 'https://api.mercadopago.com/'
    ]);
    try {
        $apiResponse = $client->request('GET', "v1/payments/{$mpId}", [
            'headers' => [
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json'
            ]
        ]);
        $body = json_decode($apiResponse->getBody(), true);
    } catch (\Exception $e) {
        \Log::error('Erro ao consultar pagamento no Mercado Pago: ' . $e->getMessage());
        return response()->json(['success' => false, 'message' => 'Erro ao consultar Mercado Pago.']);
    }

    // 3. Verifica se o pagamento foi aprovado (PIX pago)
    /*
      Status possíveis (verifique sempre a documentação atualizada):
      - approved      -> pago
      - pending       -> pendente
      - in_process    -> aguardando
      - rejected      -> rejeitado
    */
    if (isset($body['status']) && $body['status'] === 'approved') {
        // 4. Recupera o reference_id que você gravou na sua base (pode ser em external_reference)
        $reference = $body['external_reference'] ?? $mpId; // Ajuste conforme seu fluxo!

        $pg = \App\Models\Pagamento::where('reference', $reference)->first();
        if (!$pg) {
            \Log::warning('Pagamento não encontrado no banco com referência.', ['reference' => $reference]);
            return response()->json(['success' => false, 'message' => 'Pagamento não encontrado.']);
        }

        // Atualiza o status: pago
        $pg->status = 0;
        $pg->save();
        \Log::info('Pagamento confirmado e atualizado.', ['reference' => $reference]);

        // Logística de plano+usuário segue igual ao seu código, veja abaixo:
        $user = \App\Models\User::where('id', $pg->user_id)->first();
        if ($user && !empty($user->codigo_indicacao)) {
            $codigoIndicacao = $user->codigo_indicacao;
            $revendedor = \App\Models\User::where('codigo_revendedor', $codigoIndicacao)->first();
            if ($revendedor) {
                $porcentagem = floatval($revendedor->porcent_resseller ?? 0);
                $valorPagamento = floatval($pg->valor ?? 0);
                $valorCredito = ($valorPagamento * $porcentagem) / 100;
                $revendedor->credito = floatval($revendedor->credito ?? 0) + $valorCredito;
                $revendedor->save();
                \Log::info('Crédito adicionado ao revendedor', [
                    'revendedor_id' => $revendedor->id,
                    'valor_credito' => $valorCredito,
                    'total_credito' => $revendedor->credito,
                ]);
            }
        }

        $user->plan_id = $pg->plan_id;
        $dataInicio = now();
        $user->data_inicio = $dataInicio;
        $plan = \App\Models\Plan::find($pg->plan_id);
        if ($plan) {
            $dias = intval($plan->duracao_dias ?? 0);
            $dataFim = $dataInicio->copy()->addDays($dias);
            $user->data_fim = $dataFim;
        }
        $user->save();

        return response()->json(['success' => true]);
    }

    // 5. Status não é aprovado, apenas retorna ok (não faz nada)
    \Log::info('Pagamento ainda não aprovado ou foi rejeitado.', ['mpId' => $mpId, 'status' => $body['status'] ?? null]);
    return response()->json(['success' => true, 'message' => 'Pagamento não aprovado ainda.']);
}



public function statusPorReferenciaDireta($reference)
{
    $pagamento = Pagamento::where('reference', $reference)->first();

    if (!$pagamento) {
        return response()->json(['error' => 'Pagamento não encontrado.'], 404);
    }

    return response()->json(['status' => $pagamento->status]);
}




}


