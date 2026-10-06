<?php

namespace App\Http\Controllers\Prevention\Admin;

use App\Http\Controllers\Prevention\PreventionController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Toutes les actions d'administration du module sont réservées au rôle « admin ».
 */
abstract class AdminPreventionController extends PreventionController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function (Request $request, Closure $next) {
                abort_unless($request->user()?->role === 'admin', 403);

                return $next($request);
            }),
        ];
    }
}
