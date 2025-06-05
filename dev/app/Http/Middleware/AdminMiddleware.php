<?php




namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Assumindo que você tem um campo 'is_admin' no seu model User
        if (auth()->check() && auth()->user()->is_admin) {
            return $next($request);
        }

        // Caso a pessoa tente bancar o admin, mas seja só um mortal comum
        abort(403, 'Acesso negado. Você não é admin e sabe disso.');
    }
}
