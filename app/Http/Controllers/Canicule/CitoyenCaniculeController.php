<?php

namespace App\Http\Controllers\Canicule;

use App\Models\Alerte;
use App\Models\Campagne;
use App\Models\ReleveMeteo;
use App\Models\User;
use App\Models\Zone;
use App\Services\Canicule\CarteCaniculeService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Http;
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
        $zones = Zone::query()->where('active', true)->orderBy('nom')->get();
        $selectedZoneId = $request->input('zone_id');
        $zone = $selectedZoneId
            ? $zones->firstWhere('id', (int) $selectedZoneId)
            : $this->zoneCitoyen($request->user());

        if (! $zone && $selectedZoneId) {
            $zone = Zone::query()->find($selectedZoneId);
        }

        $now = now();
        $alerte = $zone?->alertes()
            ->where('statut', 'active')
            ->where('debut_validite', '<=', $now)
            ->where('fin_validite', '>=', $now)
            ->orderByRaw("CASE niveau_gravite WHEN 'rouge' THEN 3 WHEN 'orange' THEN 2 ELSE 1 END DESC")
            ->first();

        $weather = $zone ? $this->weatherForZone($zone) : null;

        return view('canicule.citoyen.meteo', [
            'zone' => $zone,
            'zones' => $zones,
            'selectedZoneId' => $zone?->id,
            'observation' => $zone?->relevesMeteo()->where('type', 'observation')->latest('date_mesure')->first(),
            'prevision' => $zone?->relevesMeteo()->where('type', 'prevision')->latest('date_mesure')->first(),
            'weather' => $weather,
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

        $campagnesDisponibles = Campagne::query()
            ->whereIn('statut', ['publiee', 'en_cours'])
            ->orderBy('date_debut')
            ->get();

        $evenements = $campagnesDisponibles
            ->filter(fn (Campagne $campagne) => $campagne->date_debut === null || $campagne->date_debut->greaterThanOrEqualTo(now()->startOfDay()))
            ->whenEmpty(fn () => $campagnesDisponibles)
            ->map(fn (Campagne $campagne) => [
                'campagne' => $campagne,
                'info' => $this->infoPourCampagne($campagne),
            ]);

        return view('canicule.citoyen.alertes', compact('zone', 'alertes', 'evenements'));
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

    private function weatherForZone(Zone $zone): ?array
    {
        $apiKey = config('services.openweather.key');

        if (blank($apiKey)) {
            return null;
        }

        $response = Http::get('https://api.openweathermap.org/data/2.5/weather', [
            'q' => $zone->nom.',TN',
            'units' => 'metric',
            'lang' => 'fr',
            'appid' => $apiKey,
        ]);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();
        $main = $data['main'] ?? [];
        $wind = $data['wind'] ?? [];
        $weather = $data['weather'][0] ?? [];

        return [
            'temperature' => (float) ($main['temp'] ?? 0),
            'humidity' => (int) ($main['humidity'] ?? 0),
            'wind_speed' => (float) ($wind['speed'] ?? 0),
            'condition' => (string) ($weather['description'] ?? 'Données indisponibles'),
            'sunrise' => data_get($data, 'sys.sunrise'),
            'sunset' => data_get($data, 'sys.sunset'),
        ];
    }

    private function infoPourCampagne(Campagne $campagne): array
    {
        $apiKey = config('assistant_ia.key');
        $default = [
            'risque' => 'canicule',
            'info' => 'Hydratez-vous régulièrement, évitez les heures les plus chaudes et choisissez des espaces ombragés.',
        ];

        $date = $campagne->date_debut ? $campagne->date_debut->format('d/m/Y') : 'date non précisée';
        $lieu = $campagne->lieu ?: 'lieu à préciser';

        if (blank($apiKey)) {
            return $default;
        }

        $prompt = sprintf(
            'Tu es un assistant de prévention météo pour un citoyen tunisien. Pour l\'événement suivant, estime le risque probable pour la date prévue : '
            .'Nom : %s. Lieu : %s. Date : %s. Description : %s. Réponds en français, en 2 lignes max, et donne d\'abord le risque parmi exactement : canicule, pluie, inondations, normal. '
            .'Puis une info utile et rassurante pour organiser l\'événement, sans mentionner que tu es une IA. Format attendu : "Risque estimé : X\nInfo : ...".',
            $campagne->titre,
            $lieu,
            $date,
            $campagne->description ?: 'Événement extérieur à suivre.'
        );

        $response = Http::withHeaders([
            'x-goog-api-key' => $apiKey,
        ])
            ->timeout((int) config('assistant_ia.timeout', 15))
            ->post('https://generativelanguage.googleapis.com/v1beta/models/'.config('assistant_ia.model', 'gemini-3.5-flash-lite').':generateContent', [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [[
                        'text' => $prompt,
                    ]],
                ]],
            ]);

        if ($response->failed()) {
            return $default;
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
        $normalized = is_string($text) ? trim(strip_tags($text)) : '';

        if ($normalized === '') {
            return $default;
        }

        preg_match('/Risque estimé\s*:\s*(canicule|pluie|inondations|normal)/i', $normalized, $riskMatch);
        preg_match('/Info\s*:\s*(.*)$/is', $normalized, $infoMatch);

        $risk = strtolower($riskMatch[1] ?? 'canicule');
        $info = trim($infoMatch[1] ?? $normalized);

        return [
            'risque' => in_array($risk, ['canicule', 'pluie', 'inondations', 'normal'], true) ? $risk : 'canicule',
            'info' => $info !== '' ? $info : $default['info'],
        ];
    }
}