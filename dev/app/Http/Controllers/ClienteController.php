<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\HistoricoPag;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use OpenApi\Annotations as OA;

class ClienteController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/clientes",
     *     summary="Criar um novo cliente",
     *     tags={"Clientes"},
     *     security={{"BearerAuth":{}}}, 
     *     @OA\RequestBody(
     *         required=true,
     *         description="Informações para criar um novo cliente",
     *         @OA\JsonContent(
     *             required={"nome", "numero"},
     *             @OA\Property(property="nome", type="string", example="João Silva"),
     *             @OA\Property(property="numero", type="string", example="1234567890")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cliente criado com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Cliente criado com sucesso!"),
     *             @OA\Property(property="cliente", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nome", type="string", example="João Silva"),
     *                 @OA\Property(property="numero", type="string", example="1234567890")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validação falhou",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Validação falhou"),
     *             @OA\Property(property="messages", type="object",
     *                 @OA\Property(property="nome", type="array", items={@OA\Items(type="string", example="O campo nome é obrigatório")}),
     *                 @OA\Property(property="numero", type="array", items={@OA\Items(type="string", example="O campo número é obrigatório")})
     *             )
     *         )
     *     )
     * )
     */
    public function create(Request $request)
    {
        // Validar os dados recebidos na requisição
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'numero' => 'required|string|max:20', // Ajuste conforme necessário
        ]);

        // Verificar se a validação falhou
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validação falhou',
                'messages' => $validator->errors()
            ], 400);
        }

        // Pegar o usuário autenticado
        $user = $request->user();

        // Criar o cliente e associar ao usuário autenticado
        $cliente = Cliente::create([
            'nome' => $request->input('nome'),
            'numero' => $request->input('numero'),
            'user_id' => $user->id  // Associando o usuário autenticado ao cliente
        ]);

        // Retornar uma resposta de sucesso com os dados do cliente criado
        return response()->json([
            'message' => 'Cliente criado com sucesso!',
            'cliente' => $cliente
        ], 201);
    }
    
    
    

    /**
     * @OA\Get(
     *     path="/api/clientes/list",
     *     summary="Listar clientes do usuário",
     *     tags={"Clientes"},
     *     security={{"BearerAuth":{}}}, 
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Número da página para paginar os resultados",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de clientes do usuário com paginação",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="data", type="array", items={
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="nome", type="string", example="João Silva"),
     *                     @OA\Property(property="numero", type="string", example="1234567890")
     *                 )
     *             }),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=50)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Usuário não autenticado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Não autorizado")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        // Pegar o usuário autenticado
        $user = $request->user();

        // Paginando os clientes relacionados ao usuário, com 10 por página
        $clientes = $user->clientes()->paginate(10);

        // Retornar a lista de clientes com informações de paginação
        return response()->json($clientes);
    }





/**
 * @OA\Get(
 *     path="/api/clientes/search",
 *     summary="Buscar clientes do usuário por nome ou número com paginação",
 *     tags={"Clientes"},
 *     security={{"BearerAuth":{}}}, 
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         required=false,
 *         description="Número da página para paginar os resultados",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         required=false,
 *         description="Termo para filtrar os clientes por nome ou número",
 *         @OA\Schema(type="string", example="João")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Lista de clientes do usuário com paginação e filtro de busca",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="current_page", type="integer", example=1),
 *             @OA\Property(property="data", type="array", items={
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="nome", type="string", example="João Silva"),
 *                     @OA\Property(property="numero", type="string", example="1234567890")
 *                 )
 *             }),
 *             @OA\Property(property="per_page", type="integer", example=10),
 *             @OA\Property(property="total", type="integer", example=50)
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Usuário não autenticado",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Não autorizado")
 *         )
 *     )
 * )
 */
public function searchClientes(Request $request)
{
    // Pegar o usuário autenticado
    $user = $request->user();

    // Log detalhado para acompanhar a requisição
    \Log::info('Iniciando a busca de clientes', [
        'user_id' => $user->id,
        'search' => $request->input('search'),
        'page' => $request->input('page', 1)
    ]);

    try {
        // Buscar pelo parâmetro "search", caso exista
        $search = $request->input('search');

        // Iniciar a query para buscar os clientes relacionados ao usuário
        $clientesQuery = $user->clientes();

        // Filtra os clientes pelo nome ou número, se o parâmetro de busca estiver presente
        if ($search) {
            \Log::info('Aplicando filtro de busca', ['search' => $search]);
            $clientesQuery->where(function($query) use ($search) {
                $query->where('nome', 'LIKE', "%$search%")
                      ->orWhere('numero', 'LIKE', "%$search%");
            });
        }

        // Paginando os resultados
        $clientes = $clientesQuery->paginate(10);

        // Log detalhado de sucesso
        \Log::info('Busca de clientes realizada com sucesso', [
            'total_clientes' => $clientes->total(),
            'current_page' => $clientes->currentPage(),
            'per_page' => $clientes->perPage()
        ]);

        // Retornar a lista de clientes com informações de paginação
        return response()->json($clientes);

    } catch (\Exception $e) {
        // Log de erro detalhado
        \Log::error('Erro ao realizar busca de clientes', [
            'error_message' => $e->getMessage(),
            'stack_trace' => $e->getTraceAsString(),
            'user_id' => $user->id,
            'search' => $search
        ]);

        // Retornar resposta de erro
        return response()->json([
            'error' => 'Ocorreu um erro ao buscar os clientes. Tente novamente mais tarde.'
        ], 500);
    }
}





    /**
 * @OA\Delete(
 *     path="/api/clientes/delete/{id}",
 *     summary="Excluir um cliente pelo ID",
 *     tags={"Clientes"},
 *     security={{"BearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID do cliente a ser excluído",
 *         @OA\Schema(type="integer", example=1)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Cliente excluído com sucesso",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="message", type="string", example="Cliente excluído com sucesso")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Cliente não encontrado",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Cliente não encontrado")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Usuário não autenticado",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="error", type="string", example="Não autorizado")
 *         )
 *     )
 * )
 */
public function deleteCliente(Request $request, $id)
{
    // Pegar o usuário autenticado
    $user = $request->user();
    
    // Encontrar o cliente vinculado ao usuário
    $cliente = $user->clientes()->find($id);
    
    if (!$cliente) {
        return response()->json(['error' => 'Cliente não encontrado'], 404);
    }
    
    // Excluir o cliente
    $cliente->delete();
    
    return response()->json(['message' => 'Cliente excluído com sucesso']);
}


/**
     * @OA\Put(
     *     path="/api/clientes/{id}",
     *     summary="Atualizar um cliente existente",
     *     tags={"Clientes"},
     *     security={{"BearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do cliente a ser atualizado",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Informações para atualizar o cliente",
     *         @OA\JsonContent(
     *             required={"nome", "numero"},
     *             @OA\Property(property="nome", type="string", example="João Silva"),
     *             @OA\Property(property="numero", type="string", example="1234567890")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cliente atualizado com sucesso",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Cliente atualizado com sucesso!"),
     *             @OA\Property(property="cliente", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nome", type="string", example="João Silva"),
     *                 @OA\Property(property="numero", type="string", example="1234567890")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validação falhou",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Validação falhou"),
     *             @OA\Property(property="messages", type="object",
     *                 @OA\Property(property="nome", type="array", items={@OA\Items(type="string", example="O campo nome é obrigatório")}),
     *                 @OA\Property(property="numero", type="array", items={@OA\Items(type="string", example="O campo número é obrigatório")})
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cliente não encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Cliente não encontrado")
     *         )
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        // Encontrar o cliente pelo ID
        $cliente = Cliente::find($id);

        // Verificar se o cliente existe
        if (!$cliente) {
            return response()->json(['error' => 'Cliente não encontrado'], 404);
        }

        // Validar os dados recebidos na requisição
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'numero' => 'required|string|max:20', // Ajuste conforme necessário
        ]);

        // Verificar se a validação falhou
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validação falhou',
                'messages' => $validator->errors()
            ], 400);
        }

        // Atualizar o cliente com os novos dados
        $cliente->update([
            'nome' => $request->input('nome'),
            'numero' => $request->input('numero'),
        ]);

        // Retornar uma resposta de sucesso com os dados do cliente atualizado
        return response()->json([
            'message' => 'Cliente atualizado com sucesso!',
            'cliente' => $cliente
        ], 200);
    }
    
    
    
    
    
    

    /**
     * Exibe o total de clientes do usuário logado.
     *
     * @OA\Get(
     *     path="/api/estatisticas-pagamentos",
     *     summary="Listar o total de clientes do usuário logado",
     *     tags={"Dashboard", "Pagamentos"},
     *     security={{"BearerAuth":{}}}, 
     *     @OA\Response(
     *         response=200,
     *         description="Total de clientes do usuário logado",
     *         @OA\JsonContent(
     *             type="object",
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
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Nenhum cliente encontrado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="error", type="string", example="Nenhum cliente encontrado para o usuário")
     *         )
     *     )
     * )
     */
    public function totalClientes(Request $request)
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

            $totalClientes = $clientes->count();

            // Retorna as estatísticas
            return response()->json([
                'total_clientes' => $totalClientes
            ]);
        } catch (\Exception $e) {
            // Loga o erro para debug
            Log::error('Erro ao listar total de clientes: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao listar total de clientes'], 500);
        }
    }

    




}