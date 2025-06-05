<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use App\Http\Middleware\VerificaPlano;
use OpenApi\Annotations as OA;

class LoginController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Autenticar o usuário e gerar um token de acesso",
     *     tags={"Autenticação"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Credenciais do usuário para login",
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", example="usuario@exemplo.com"),
     *             @OA\Property(property="password", type="string", example="senha123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login bem-sucedido, token gerado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="user", type="object", 
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="João Silva"),
     *                 @OA\Property(property="email", type="string", example="usuario@exemplo.com")
     *             ),
     *             @OA\Property(property="token", type="string", example="12|abc123...xyz")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Credenciais inválidas",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Credenciais inválidas")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        // Validação das credenciais
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Busca do usuário
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        // Remove tokens antigos
        $user->tokens->each(fn($token) => $token->delete());

        // Cria novo token
        $token = $user->createToken('auth_token', ['*'])->plainTextToken;

        // Loga o usuário manualmente no sistema (para que Auth::user funcione)
        Auth::login($user);

        // Executa o middleware VerificaPlano manualmente
        $middleware = App::make(VerificaPlano::class);
        $response = $middleware->handle($request, fn() => null);

        if ($response) {
            // Se o middleware bloquear (por plano), retorna a resposta dele
            return response()->json(['message' => 'Plano obrigatório!'], 403);
        }

        // Retorna token e dados do usuário autenticado
        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }
}
