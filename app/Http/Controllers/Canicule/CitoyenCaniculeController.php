<?php

namespace App\Http\Controllers\Canicule;

use App\Models\Alerte;
use App\Models\ReleveMeteo;
use App\Models\User;
use App\Models\Zone;
use App\Services\Canicule\CarteCaniculeService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CitoyenCaniculeController extends \App\Http\Controllers\Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function (Request $request, Closure $next) {
                abort_unless($request->user()?->role === 'user', 403);

                return $next($request);
            }),
        ];
    }

    public function meteo(Request $request): View
    {
        $zone = $this->zoneCitoyen($request->user());
        $now = now();
        $alerte = $zone?->alertes()
            ->where('statut', 'active')
            ->where('debut_validite', '<=', $now)
            ->where('fin_validite', '>=', $now)
            ->orderByRaw("CASE niveau_gravite WHEN 'rouge' THEN 3 WHEN 'orange' THEN 2 ELSE 1 END DESC")
            ->first();

        return view('canicule.citoyen.meteo', [
            'zone' => $zone,
            'observation' => $zone?->relevesMeteo()->where('type', 'observation')->latest('date_mesure')->first(),
            'prevision' => $zone?->relevesMeteo()->where('type', 'prevision')->latest('date_mesure')->first(),
            'alerte' => $alerte,
        ]);
    }

    public function alertes(Request $request): View
    {
        $zone = $this->zoneCitoyen($request->user());
        $now = now();
        $alertes = Alerte::with('zone')
            ->where('statut', 'active')
            ->where('debut_validite', '<=', $now)
            ->where('fin_validite', '>=', $now)
            ->orderByRaw('CASE WHEN zone_id = ? THEN 0 ELSE 1 END', [$zone?->id ?? 0])
            ->orderByRaw("CASE niveau_gravite WHEN 'rouge' THEN 3 WHEN 'orange' THEN 2 ELSE 1 END DESC")
            ->orderBy('debut_validite')
            ->get();

        return view('canicule.citoyen.alertes', compact('zone', 'alertes'));
    }

    public function carte(CarteCaniculeService $carte): View
    {
        return view('canicule.citoyen.carte', ['geojson' => $carte->geojson()]);
    }

    private function zoneCitoyen(User $user): ?Zone
    {
        $nom = $user->governorate === 'Le Kef' ? 'El Kef' : $user->governorate;

        return Zone::where('nom', $nom)->first();
    }
}