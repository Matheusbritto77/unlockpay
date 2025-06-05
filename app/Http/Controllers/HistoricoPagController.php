<?php

namespace App\Http\Controllers;

use App\Models\HistoricoPag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str; // Para gerar um UUID
use App\Models\Cliente;

use OpenApi\Annotations as OA;


class HistoricoPagController extends Controller
{
   /**
 * @OA\Post(
 *     path="/api/pagamentos",
 *     summary="Registrar um pagamento",
 *     tags={"Pagamentos"},
 *     security={{"BearerAuth":{}}}, 
 *     @OA\RequestBody(
 *         required=true,
 *         description="Informações para registrar um pagamento",
 *         @OA\JsonContent(
 *             required={"cliente_id", "mp_token", "valor", "nome", "numero", "instancia"},
 *             @OA\Property(property="cliente_id", type="integer", example=1),
 *             @OA\Property(property="mp_token", type="string", example="YOUR_MP_ACCESS_TOKEN"),
 *             @OA\Property(property="valor", type="number", format="float", example=100.50),
 *             @OA\Property(property="descricao", type="string", example="Pagamento via QR Code"),
 *             @OA\Property(property="nome", type="string", example="João Silva"),
 *             @OA\Property(property="numero", type="string", example="1234567890"),
 *             @OA\Property(property="instancia", type="string", example="instancia_1"),
 *             @OA\Property(property="custo", type="number", format="float", example=5.00, nullable=true)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Pagamento registrado com sucesso",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Histórico de pagamento criado com sucesso"),
 *             @OA\Property(property="payment_url", type="string", example="https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=123456"),
 *             @OA\Property(property="qr_code", type="string", example="https://www.mercadopago.com.br/qr_code_url"),
 *             @OA\Property(property="qr_code_base64", type="string", example="base64encodedQRcode"),
 *             @OA\Property(property="reference_id", type="string", example="123456"),
 *             @OA\Property(property="historico_pag", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="nome", type="string", example="João Silva"),
 *                 @OA\Property(property="numero", type="string", example="1234567890"),
 *                 @OA\Property(property="status", type="string", example="não pago"),
 *                 @OA\Property(property="cliente_id", type="integer", example=1),
 *                 @OA\Property(property="descricao", type="string", example="Pagamento via QR Code"),
 *                 @OA\Property(property="reference_id", type="string", example="123456"),
 *                 @OA\Property(property="instancia", type="string", example="instancia_1"),
 *                 @OA\Property(property="custo", type="number", format="float", example=5.00, nullable=true)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Erro de validação",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Validação falhou"),
 *             @OA\Property(property="messages", type="object",
 *                 @OA\Property(property="cliente_id", type="array", items={@OA\Items(type="string", example="O campo cliente_id é obrigatório")}),
 *                 @OA\Property(property="mp_token", type="array", items={@OA\Items(type="string", example="O campo mp_token é obrigatório")}),
 *                 @OA\Property(property="valor", type="array", items={@OA\Items(type="string", example="O campo valor é obrigatório")}),
 *                 @OA\Property(property="nome", type="array", items={@OA\Items(type="string", example="O campo nome é obrigatório")}),
 *                 @OA\Property(property="numero", type="array", items={@OA\Items(type="string", example="O campo número é obrigatório")}),
 *                 @OA\Property(property="instancia", type="array", items={@OA\Items(type="string", example="O campo instancia é obrigatório")}),
 *                 @OA\Property(property="custo", type="array", items={@OA\Items(type="string", example="O campo custo deve ser numérico")})
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erro ao criar o pagamento",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Erro ao criar pagamento: [erro específico]")
 *         )
 *     )
 * )
 */
public function register(Request $request)
{
    \Log::info('Início do processo de registro de pagamento', $request->all());

    try {
        // Validação dos dados de entrada, incluindo o campo custo
        $validatedData = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'mp_token' => 'required|string',
            'valor' => 'required|numeric|min:0.01',
            'descricao' => 'nullable|string',
            'nome' => 'required|string|max:255',
            'numero' => 'required|string|max:255',
            'instancia' => 'required|string|max:255',
            'custo' => 'nullable|numeric|min:0.01',
            'categoria_name'=> 'nullable|string|max:255',
        ]);

        \Log::info('Dados validados com sucesso', $validatedData);

        // Cabeçalhos para a requisição
        $headers = [
            'Authorization' => 'Bearer ' . $request->mp_token,
            'Content-Type' => 'application/json',
            'X-Idempotency-Key' => Str::uuid()->toString(), // Gerar uma chave única para idempotência
        ];

        \Log::info('Cabeçalhos configurados', $headers);

        // Criação do pagamento
        $response = Http::withHeaders($headers)->post('https://api.mercadopago.com/v1/payments', [
            'transaction_amount' => (float) $request->valor,
            'token' => $request->mp_token,
            'description' => $request->descricao ?? 'Pagamento via QR Code',
            'payment_method_id' => 'pix', // Altere conforme o método de pagamento
            'payer' => [
                'email' => 'exemplo@dominio.com', // Substitua por um e-mail válido
                'first_name' => $request->nome,
                'last_name' => '', // Caso tenha, adicione o sobrenome aqui
            ],
        ]);

        \Log::info('Resposta da API de pagamento recebida', ['status' => $response->status(), 'body' => $response->body()]);

        if ($response->failed()) {
            \Log::error('Erro ao criar pagamento', ['status' => $response->status(), 'error' => $response->body()]);
            return response()->json(['message' => 'Erro ao criar pagamento: ' . $response->body()], 500);
        }

        // Salvar os dados na tabela historicopag, incluindo o custo
        $historicoPag = HistoricoPag::create([
            'cliente_id' => $request->cliente_id,
            'nome' => $request->nome,
            'instancia' => $request->instancia,
            'numero' => $request->numero,
            'descricao' => $request->descricao ?? 'Pagamento via QR Code',
            'status' => 'nao_pago',
            'reference_id' => $response->json()['id'], // Usando o ID da preferência criada
            'valor' => $request->valor,
            'custo' => $request->custo ?? null, // Adicionando o custo, que pode ser null
            'categoria_name' =>$request->categoria_name ?? null,
        ]);

        \Log::info('Histórico de pagamento salvo no banco de dados', $historicoPag->toArray());

        // Resposta com URL de pagamento e QR Code, incluindo o custo no objeto historico_pag
        $responseData = [
            'message' => 'Histórico de pagamento criado com sucesso',
            'qr_code' => $response->json()['point_of_interaction']['transaction_data']['qr_code'],
            'qr_code_base64' => $response->json()['point_of_interaction']['transaction_data']['qr_code_base64'],
            'reference_id' => $response->json()['id'],
            'historico_pag' => $historicoPag,
        ];

        \Log::info('Resposta enviada ao cliente', $responseData);

        return response()->json($responseData, 201);
    } catch (\Exception $e) {
        \Log::error('Exceção inesperada durante o registro de pagamento', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return response()->json(['message' => 'Erro inesperado ao criar pagamento: ' . $e->getMessage()], 500);
    }
}




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
     *             @OA\Property(property="total_nao_pago", type="integer", example=5),
     *             @OA\Property(property="total_pago", type="integer", example=10),
     *             @OA\Property(property="total_clientes", type="integer", example=15)
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
    public function  resumoPagamentos(Request $request)
    {
        try {
            // Obtém o usuário logado
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Usuário não autorizado'], 401);
            }

            // Obtém os clientes associados ao usuário logado (baseado no user_id)
            $clientes = Cliente::where('user_id', $user->id)->get();  // Pega todos os clientes relacionados ao usuário logado

            // Verifica se o usuário tem clientes associados
            if ($clientes->isEmpty()) {
                return response()->json(['error' => 'Nenhum cliente encontrado para o usuário'], 404);
            }

            // Inicializa os contadores
            $totalNaoPago = 0;
            $totalPago = 0;
            $totalClientes = $clientes->count();  // Conta o total de clientes relacionados ao usuário

            // Para cada cliente, conta os pagamentos com os status 'não pago' e 'pago'
            foreach ($clientes as $cliente) {
                // Verifica os pagamentos do cliente na tabela HistoricoPag
                $historicos = HistoricoPag::where('cliente_id', $cliente->id)
                    ->get(['status']);  // Obtém apenas o status dos pagamentos

                // Incrementa os contadores
                foreach ($historicos as $historico) {
                    if ($historico->status == 'nao_pago') {
                        $totalNaoPago++;
                    } elseif ($historico->status == 'pago') {
                        $totalPago++;
                    }
                }
            }

            // Retorna as estatísticas
            return response()->json([
                'total_nao_pago' => $totalNaoPago,
                'total_pago' => $totalPago,
                
            ]);

        } catch (\Exception $e) {
            // Loga o erro para debug
            Log::error('Erro ao listar estatísticas de pagamentos: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao listar estatísticas de pagamentos'], 500);
        }
    }


    


    

/**
 * Exibe os pagamentos do usuário logado com filtros opcionais de data, pago, não pago e nome.
 * 
 * @OA\Get(
 *     path="/api/historico-pagamentos",
 *     summary="Listar pagamentos do usuário logado com filtros opcionais",
 *     tags={"Pagamentos"},
 *     security={{"BearerAuth":{}}},
 *     @OA\Parameter(
 *         name="data_inicio",
 *         in="query",
 *         description="Data de início do pagamento no formato YYYY-MM-DD",
 *         required=false,
 *         @OA\Schema(type="string", format="date")
 *     ),
 *     @OA\Parameter(
 *         name="data_fim",
 *         in="query",
 *         description="Data de fim do pagamento no formato YYYY-MM-DD",
 *         required=false,
 *         @OA\Schema(type="string", format="date")
 *     ),
 *     @OA\Parameter(
 *         name="pago",
 *         in="query",
 *         description="Filtro para pagamentos pagos",
 *         required=false,
 *         @OA\Schema(type="boolean")
 *     ),
 *     @OA\Parameter(
 *         name="nao_pago",
 *         in="query",
 *         description="Filtro para pagamentos não pagos",
 *         required=false,
 *         @OA\Schema(type="boolean")
 *     ),
 *     @OA\Parameter(
 *         name="nome",
 *         in="query",
 *         description="Nome do cliente para filtrar",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Número da página para paginação",
 *         required=false,
 *         @OA\Schema(type="integer", default=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Histórico de pagamentos do usuário logado filtrados",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="cliente_id", type="integer", example=1),
 *                     @OA\Property(property="nome", type="string", example="João Silva"),
 *                     @OA\Property(property="numero", type="string", example="1234567890"),
 *                     @OA\Property(property="descricao", type="string", example="Pagamento via QR Code"),
 *                     @OA\Property(property="status", type="string", example="não pago"),
 *                     @OA\Property(property="valor", type="number", format="float", example=150.75),
 *                     @OA\Property(property="data", type="string", format="date", example="2025-03-30")
 *                 )
 *             ),
 *             @OA\Property(property="current_page", type="integer", example=1),
 *             @OA\Property(property="last_page", type="integer", example=5),
 *             @OA\Property(property="per_page", type="integer", example=10),
 *             @OA\Property(property="total", type="integer", example=45)
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Não autorizado",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Não autorizado")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Nenhum cliente encontrado",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Nenhum cliente encontrado para o usuário")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erro interno do servidor",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Erro ao listar histórico de pagamentos")
 *         )
 *     )
 * )
 */
public function listarHistoricoPagamentos(Request $request)
{
    try {
        // Obtém o usuário logado
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Usuário não autorizado'], 401);
        }

        // Obtém os clientes associados ao usuário logado
        $clientes = $user->clientes;

        if ($clientes->isEmpty()) {
            return response()->json(['error' => 'Nenhum cliente encontrado para o usuário'], 404);
        }

        $query = HistoricoPag::query();

        // Aplica os filtros opcionais
        if ($request->has('data_inicio') && $request->has('data_fim')) {
            $query->whereBetween('created_at', [$request->input('data_inicio'), $request->input('data_fim')]);
        } elseif ($request->has('data_inicio')) {
            $query->whereDate('created_at', '>=', $request->input('data_inicio'));
        } elseif ($request->has('data_fim')) {
            $query->whereDate('created_at', '<=', $request->input('data_fim'));
        }

        // Ajuste para lidar com booleanos como strings
        if ($request->has('pago') && filter_var($request->input('pago'), FILTER_VALIDATE_BOOLEAN)) {
            $query->where('status', 'pago');
        }

        if ($request->has('nao_pago') && filter_var($request->input('nao_pago'), FILTER_VALIDATE_BOOLEAN)) {
            $query->where('status', 'nao_pago');
        }

        // Filtro por nome
        if ($request->has('nome')) {
            $nome = $request->input('nome');
            $query->where('nome', 'LIKE', "%{$nome}%");
        }

        // Obtém os IDs dos clientes
        $clienteIds = $clientes->pluck('id');

        // Filtra os pagamentos dos clientes do usuário
        $historicos = $query->whereIn('cliente_id', $clienteIds)
            ->paginate(20, ['id', 'cliente_id', 'nome', 'numero', 'descricao', 'status', 'valor', 'created_at']);

        // Formata os dados para o retorno
        $historicosPag = $historicos->map(function ($historico) {
            return [
                'id' => $historico->id,
                'cliente_id' => $historico->cliente_id,
                'nome'       => $historico->nome,
                'numero'     => $historico->numero,
                'descricao'  => $historico->descricao,
                'status'     => $historico->status,
                'valor'      => $historico->valor,
                'data'       => $historico->created_at->format('Y-m-d'),
            ];
        });

        

        return response()->json([
            'data' => $historicosPag,
            'current_page' => $historicos->currentPage(),
            'last_page' => $historicos->lastPage(),
            'per_page' => $historicos->perPage(),
            'total' => $historicos->total(),
            
        ]);

    } catch (\Exception $e) {
        Log::error('Erro ao listar histórico de pagamentos: ' . $e->getMessage());
        return response()->json(['error' => 'Erro ao listar histórico de pagamentos'], 500);
    }

}




/**
 * @OA\Delete(
 *     path="/api/historico-pagamentos/{id}",
 *     summary="Excluir um pagamento do histórico",
 *     tags={"Pagamentos"},
 *     security={{"BearerAuth":{}}}, 
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID do histórico de pagamento a ser excluído",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Histórico de pagamento excluído com sucesso",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Histórico de pagamento excluído com sucesso")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Histórico de pagamento não encontrado",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Histórico de pagamento não encontrado")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erro ao excluir o histórico de pagamento",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Erro ao excluir o histórico de pagamento")
 *         )
 *     )
 * )
 */
public function delete($id)
{
    try {
        // Verifica se o ID é válido
        if (!is_numeric($id)) {
            return response()->json(['error' => 'ID inválido'], 400);
        }

        // Busca o histórico de pagamento pelo ID
        $historicoPag = HistoricoPag::find($id);

        // Verifica se o pagamento foi encontrado
        if (!$historicoPag) {
            return response()->json(['error' => 'Histórico de pagamento não encontrado'], 404);
        }

        // Exclui o pagamento
        $historicoPag->delete();

        // Retorna uma resposta de sucesso
        return response()->json(['message' => 'Histórico de pagamento excluído com sucesso'], 200);
    } catch (\Exception $e) {
        // Registra o erro e retorna a resposta de erro
        Log::error('Erro ao excluir histórico de pagamento: ' . $e->getMessage());
        return response()->json(['error' => 'Erro ao excluir histórico de pagamento'], 500);
    }
}




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
 *             type="array",
 *             @OA\Items(
 *                 @OA\Property(property="data", type="string", example="2025-03-15"),
 *                 @OA\Property(property="pago", type="integer", example=5),
 *                 @OA\Property(property="nao_pago", type="integer", example=3),
 *                 @OA\Property(property="valor_pago", type="string", example="R$ 950,00"),
 *                 @OA\Property(property="valor_nao_pago", type="string", example="R$ 300,50"),
 *                 @OA\Property(property="total_valor", type="string", example="R$ 1.250,50")
 *             )
 *         )
 *     )
 * )
 */
public function getResumoPorPeriodo(Request $request)
{
    $request->validate([
        'data_inicio' => 'required|date',
        'data_fim' => 'required|date|after_or_equal:data_inicio',
    ]);

    $user = Auth::user();
    if (!$user) {
        return response()->json(['error' => 'Não autorizado'], 401);
    }

    $clientesIds = \App\Models\Cliente::where('user_id', $user->id)->pluck('id');

    $pagamentos = \App\Models\HistoricoPag::whereIn('cliente_id', $clientesIds)
        ->whereBetween('created_at', [
            $request->data_inicio . ' 00:00:00',
            $request->data_fim . ' 23:59:59'
        ])
        ->get()
        ->groupBy(function ($item) {
            return $item->created_at->format('Y-m-d');
        });

    $resumo = [];

    foreach ($pagamentos as $data => $itens) {
        $pago = $itens->where('status', 'pago')->count();
        $naoPago = $itens->where('status', 'nao_pago')->count();

        $valorPago = $itens->where('status', 'pago')->sum('valor');
        $valorNaoPago = $itens->where('status', 'nao_pago')->sum('valor');

        $resumo[] = [
            'data' => $data,
            'pago' => $pago,
            'nao_pago' => $naoPago,
            'valor_pago' => 'R$ ' . number_format($valorPago, 2, ',', '.'),
            'valor_nao_pago' => 'R$ ' . number_format($valorNaoPago, 2, ',', '.'),
            'total_valor' => 'R$ ' . number_format($valorPago + $valorNaoPago, 2, ',', '.'),
        ];
    }

    return response()->json($resumo);
}








/**
 * @OA\Post(
 *     path="/api/cobrar",
 *     summary="Gerar cobrança via Mercado Pago e enviar mensagem via WhatsApp",
 *     tags={"Pagamentos"},
 *     security={{"BearerAuth":{}}}, 
 *     @OA\RequestBody(
 *         required=true,
 *         description="Dados para gerar a cobrança",
 *         @OA\JsonContent(
 *             required={"id", "mp_token"},
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="mp_token", type="string", example="YOUR_MP_ACCESS_TOKEN")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Cobrança gerada e mensagem enviada com sucesso",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Cobrança gerada e mensagem enviada com sucesso")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Erro de validação",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Validação falhou")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Histórico de pagamento não encontrado",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Histórico de pagamento não encontrado")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erro ao gerar cobrança ou enviar mensagem",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Erro ao gerar cobrança ou enviar mensagem")
 *         )
 *     )
 * )
 */
public function cobrar(Request $request)
{
    try {
        // Validação dos dados de entrada
        $validatedData = $request->validate([
            'id' => 'required|numeric|exists:historicopag,id', // Corrige o nome da tabela para historicopag
            'mp_token' => 'required|string',
        ]);

        \Log::info('Dados validados para cobrança', $validatedData);

        // Buscar o registro de pagamento pelo ID
        $historicoPag = \App\Models\HistoricoPag::find($request->id);

        if (!$historicoPag) {
            \Log::error('Histórico de pagamento não encontrado', ['id' => $request->id]);
            return response()->json(['error' => 'Histórico de pagamento não encontrado'], 404);
        }

        // Cabeçalhos para a requisição ao Mercado Pago
        $headers = [
            'Authorization' => 'Bearer ' . $request->mp_token,
            'Content-Type' => 'application/json',
            'X-Idempotency-Key' => Str::uuid()->toString(),
        ];

        \Log::info('Cabeçalhos configurados para API Mercado Pago', $headers);

        // Criação do pagamento via Mercado Pago
        $response = Http::withHeaders($headers)->post('https://api.mercadopago.com/v1/payments', [
            'transaction_amount' => (float) $historicoPag->valor,
            'token' => $request->mp_token,
            'description' => $historicoPag->descricao ?? 'Pagamento via QR Code',
            'payment_method_id' => 'pix',
            'payer' => [
                'email' => 'exemplo@dominio.com',
                'first_name' => $historicoPag->nome,
                'last_name' => '',
            ],
        ]);

        \Log::info('Resposta da API Mercado Pago recebida', ['status' => $response->status(), 'body' => $response->body()]);

        if ($response->failed()) {
            \Log::error('Erro ao criar pagamento no Mercado Pago', ['status' => $response->status(), 'error' => $response->body()]);
            return response()->json(['message' => 'Erro ao criar pagamento: ' . $response->body()], 500);
        }

        // Extrair dados da resposta
        $qrCode = $response->json()['point_of_interaction']['transaction_data']['qr_code'];
        $qrCodeBase64 = $response->json()['point_of_interaction']['transaction_data']['qr_code_base64'];
        $referenceId = $response->json()['id'];

        // Atualizar o reference_id no registro do histórico
        $historicoPag->update(['reference_id' => $referenceId]);
        \Log::info('Reference ID atualizado no histórico de pagamento', ['reference_id' => $referenceId]);

        // Enviar mensagem via WhatsApp
        $apiUrl = "https://dapleserver.com/client/sendMessage/{$historicoPag->instancia}";
        $apiToken = "asdfghjksjhggbnmfdfghjhgf"; // Substitua pelo token real da API do WhatsApp

        // 1ª Chamada - Enviar o QR Code como imagem
        $mediaPayload = [
            'chatId' => "{$historicoPag->numero}@c.us",
            'contentType' => 'MessageMedia',
            'content' => [
                'mimetype' => 'image/png',
                'data' => $qrCodeBase64,
                'filename' => 'qrcode.png',
            ],
        ];

        $mediaResponse = Http::withHeaders(['x-api-key' => $apiToken, 'Content-Type' => 'application/json'])
            ->post($apiUrl, $mediaPayload);

        if ($mediaResponse->failed()) {
            \Log::error('Erro ao enviar QR Code via WhatsApp', ['status' => $mediaResponse->status(), 'error' => $mediaResponse->body()]);
            return response()->json(['message' => 'Erro ao enviar QR Code via WhatsApp: ' . $mediaResponse->body()], 500);
        }

        \Log::info('QR Code enviado com sucesso via WhatsApp', ['numero' => $historicoPag->numero]);

        // 2ª Chamada - Enviar mensagem de texto introdutória
        $mensagemTextoPayload = [
            'chatId' => "{$historicoPag->numero}@c.us",
            'contentType' => 'string',
            'content' => "Olá! Sua Cobrança para *{$historicoPag->descricao}* no valor de *R$ " . number_format($historicoPag->valor, 2, ',', '.') . "* foi gerada. Copie e cole o código abaixo:",
        ];

        $mensagemTextoResponse = Http::withHeaders(['x-api-key' => $apiToken, 'Content-Type' => 'application/json'])
            ->post($apiUrl, $mensagemTextoPayload);

        if ($mensagemTextoResponse->failed()) {
            \Log::error('Erro ao enviar mensagem de texto via WhatsApp', ['status' => $mensagemTextoResponse->status(), 'error' => $mensagemTextoResponse->body()]);
            return response()->json(['message' => 'Erro ao enviar mensagem de texto via WhatsApp: ' . $mensagemTextoResponse->body()], 500);
        }

        \Log::info('Mensagem de texto enviada com sucesso via WhatsApp', ['numero' => $historicoPag->numero]);

        // 3ª Chamada - Enviar o código "copia e cola"
        $copiaColaPayload = [
            'chatId' => "{$historicoPag->numero}@c.us",
            'contentType' => 'string',
            'content' => $qrCode, // Envia o código "copia e cola"
        ];

        $copiaColaResponse = Http::withHeaders(['x-api-key' => $apiToken, 'Content-Type' => 'application/json'])
            ->post($apiUrl, $copiaColaPayload);

        if ($copiaColaResponse->failed()) {
            \Log::error('Erro ao enviar código copia e cola via WhatsApp', ['status' => $copiaColaResponse->status(), 'error' => $copiaColaResponse->body()]);
            return response()->json(['message' => 'Erro ao enviar código copia e cola via WhatsApp: ' . $copiaColaResponse->body()], 500);
        }

        \Log::info('Código copia e cola enviado com sucesso via WhatsApp', ['numero' => $historicoPag->numero]);

        // Resposta de sucesso
        $responseData = [
            'message' => 'Cobrança gerada e mensagem enviada com sucesso',
        ];

        \Log::info('Resposta de sucesso enviada ao cliente', $responseData);

        return response()->json($responseData, 200);
    } catch (\Exception $e) {
        \Log::error('Erro ao processar cobrança', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        return response()->json(['message' => 'Erro ao gerar cobrança ou enviar mensagem: ' . $e->getMessage()], 500);
    }
}
}




