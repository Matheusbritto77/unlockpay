<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificaPlano
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->plan_id == 1) {
            // Se for request para API, retorna 403 (opcional)
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Plano obrigatório!'], 403);
            }
            // Redireciona para a página/rota de planos
            return redirect()->route('planos');
        }

        return $next($request);
    }
}