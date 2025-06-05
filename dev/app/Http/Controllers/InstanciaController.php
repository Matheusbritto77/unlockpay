<?php

namespace App\Http\Controllers;

use App\Models\Instancia;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InstanciaController extends Controller
{
    

    /**
     * Lista todas as instâncias do usuário atual.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $instancias = Instancia::where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $instancias,
                'message' => 'Instâncias recuperadas com sucesso.'
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao listar instâncias: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar instâncias. Por favor, tente novamente.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
 * Cria uma nova instância com nome único gerado automaticamente e inicia sessão no WhatsApp.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
public function store(Request $request)
{
    try {
        // Gera um nome único para a instância
        $name = $this->generateUniqueInstanceName();
        $status = 'active'; // Define o status como ativo por padrão

        // Chama a API do WhatsApp para iniciar a sessão
        $whatsappApiUrl = "https://dapleserver.com/session/start/{$name}";
        $whatsappApiToken = "asdfghjksjhggbnmfdfghjhgf";

        // Configura o cliente HTTP para a chamada à API do WhatsApp
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $whatsappApiUrl, [
            'headers' => [
                'x-api-key' => $whatsappApiToken,
            ],
            'timeout' => 10, // Timeout de 10 segundos para evitar longas esperas
        ]);

        // Verifica o status da resposta da API do WhatsApp
        if ($response->getStatusCode() === 200) {
            $responseBody = json_decode($response->getBody()->getContents(), true);
            \Log::info("Resposta da API WhatsApp: " . json_encode($responseBody));

            // Verifica se a API retornou sucesso
            if (isset($responseBody['success']) && $responseBody['success'] === true) {
                // Salva os dados no banco de dados
                $instancia = new Instancia();
                $instancia->name = $name;
                $instancia->status = $status;
                $instancia->user_id = Auth::id();
                $instancia->save();

                // Retorna a resposta com nome e status
                return response()->json([
                    'success' => true,
                    'data' => [
                        'name' => $instancia->name,
                        'status' => $instancia->status
                    ],
                    'message' => 'Instância criada com sucesso.'
                ], Response::HTTP_CREATED);
            } else {
                \Log::error("Falha ao iniciar sessão no WhatsApp: Resposta da API não indica sucesso.");
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao iniciar sessão no WhatsApp. Por favor, tente novamente.'
                ], Response::HTTP_BAD_REQUEST);
            }
        } else {
            \Log::error("Falha ao chamar API do WhatsApp. Status Code: " . $response->getStatusCode());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao comunicar com o servidor do WhatsApp. Por favor, tente novamente.'
            ], Response::HTTP_BAD_REQUEST);
        }
    } catch (\GuzzleHttp\Exception\RequestException $e) {
        \Log::error("Erro ao chamar API do WhatsApp: " . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erro ao comunicar com o servidor do WhatsApp. Por favor, tente novamente.'
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    } catch (\Exception $e) {
        \Log::error("Erro ao criar instância: " . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erro ao criar instância. Por favor, tente novamente.'
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

/**
 * Gera um nome único para a instância baseado em um prefixo e timestamp.
 *
 * @return string
 */
private function generateUniqueInstanceName(): string
{
    $prefix = 'instance_';
    $attempt = 0;
    do {
        // Usa timestamp + tentativa para evitar colisões
        $name = $prefix . time() . ($attempt > 0 ? "_$attempt" : '');
        $attempt++;
    } while (Instancia::where('name', $name)->exists());

    return $name;
}



    /**
     * Exibe os detalhes de uma instância específica.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $instancia = Instancia::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $instancia,
                'message' => 'Instância recuperada com sucesso.'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Instância não encontrada.'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error('Erro ao recuperar instância: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar instância. Por favor, tente novamente.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
 * Remove uma instância específica com base no nome fornecido via POST.
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\Response
 */
public function destroy(Request $request)
{
    try {
        // Validar a entrada do nome da instância
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $sessionId = $request->input('name');
        $whatsappApiUrl = "https://dapleserver.com/session/terminate/{$sessionId}";
        $whatsappApiToken = "asdfghjksjhggbnmfdfghjhgf";

        // Configurar o cliente HTTP para chamar a API do WhatsApp
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $whatsappApiUrl, [
            'headers' => [
                'x-api-key' => $whatsappApiToken,
            ],
            'timeout' => 10, // Timeout de 10 segundos para evitar longas esperas
        ]);

        // Verificar o status da resposta da API do WhatsApp
        if ($response->getStatusCode() === 200) {
            $responseBody = json_decode($response->getBody()->getContents(), true);
            \Log::info("Resposta da API WhatsApp (terminate session): " . json_encode($responseBody));

            // Verificar se a API retornou sucesso (ajuste conforme a estrutura da resposta da API)
            if (isset($responseBody['success']) && $responseBody['success'] === true) {
                // Procurar a instância pelo nome e user_id
                $instancia = Instancia::where('name', $sessionId)
                    ->where('user_id', Auth::id())
                    ->firstOrFail();

                // Excluir a instância
                $instancia->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Instância excluída com sucesso.'
                ]);
            } else {
                \Log::error("Falha ao terminar sessão no WhatsApp: Resposta da API não indica sucesso.");
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao terminar sessão no WhatsApp. Por favor, tente novamente.'
                ], Response::HTTP_BAD_REQUEST);
            }
        } else {
            \Log::error("Falha ao chamar API do WhatsApp. Status Code: " . $response->getStatusCode());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao comunicar com o servidor do WhatsApp. Por favor, tente novamente.'
            ], Response::HTTP_BAD_REQUEST);
        }
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erro de validação',
            'errors' => $e->errors()
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Instância não encontrada no banco de dados.'
        ], Response::HTTP_NOT_FOUND);
    } catch (\GuzzleHttp\Exception\RequestException $e) {
        \Log::error("Erro ao chamar API do WhatsApp: " . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erro ao comunicar com o servidor do WhatsApp. Por favor, tente novamente.'
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    } catch (\Exception $e) {
        \Log::error("Erro ao excluir instância: " . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Erro ao excluir instância. Por favor, tente novamente.'
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

    /**
     * Ativa uma instância específica.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function activate($id)
    {
        try {
            $instancia = Instancia::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $instancia->activate();

            return response()->json([
                'success' => true,
                'data' => $instancia,
                'message' => 'Instância ativada com sucesso.'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Instância não encontrada.'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error('Erro ao ativar instância: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao ativar instância. Por favor, tente novamente.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Desativa uma instância específica.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function deactivate($id)
    {
        try {
            $instancia = Instancia::where('id', $id)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            $instancia->deactivate();

            return response()->json([
                'success' => true,
                'data' => $instancia,
                'message' => 'Instância desativada com sucesso.'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Instância não encontrada.'
            ], Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            Log::error('Erro ao desativar instância: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Erro ao desativar instância. Por favor, tente novamente.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}