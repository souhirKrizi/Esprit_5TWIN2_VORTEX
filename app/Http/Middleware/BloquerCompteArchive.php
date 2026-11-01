<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Déconnecte un compte archivé alors qu'il était déjà connecté.
 */
class BloquerCompteArchive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->archived) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('signin')->with('status', 'Votre compte est archivé. Contactez l’administrateur pour le récupérer.');
    }
}
