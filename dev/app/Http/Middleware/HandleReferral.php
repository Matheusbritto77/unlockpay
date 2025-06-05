<?php



namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;

class HandleReferral
{
    public function handle(Request $request, Closure $next)
    {
        $codigo = $request->segment(1); // Pega o primeiro segmento da URL

        if ($codigo && User::where('codigo_revendedor', $codigo)->exists()) {
            // Verifica se a requisição é HTTPS
            $isSecure = $request->isSecure();

            // Define o cookie com o valor do código de indicação
            Cookie::queue(
                Cookie::make('indicacao', $codigo, 30 * 24 * 60, null, null, $isSecure, false)
            );
        }

        return $next($request);
    }
}