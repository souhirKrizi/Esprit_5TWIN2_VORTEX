<?php

namespace App\Services\Canicule;

use App\Models\Zone;
use Illuminate\Support\Js;

class CarteCaniculeService
{
    public function geojson(): string
    {
        $now = now();
        $zones = Zone::where('active', true)->with(['alertes' => fn ($query) => $query
            ->where('statut', 'active')
            ->where('debut_validite', '<=', $now)
            ->where('fin_validite', '>=', $now)
            ->orderByRaw("CASE niveau_gravite WHEN 'rouge' THEN 3 WHEN 'orange' THEN 2 ELSE 1 END DESC")])->orderBy('nom')->get();

        $features = $zones->map(function (Zone $zone): array {
            $alerte = $zone->alertes->first();

            return [
                'type' => 'Feature',
                'geometry' => json_decode($zone->geometrie_geojson, true, flags: JSON_THROW_ON_ERROR),
                'properties' => [
                    'id' => $zone->id,
                    'nom' => $zone->nom,
                    'niveau' => $alerte?->niveau_gravite,
                    'alerte' => $alerte?->titre,
                    'description' => $alerte?->description,
                    'fin' => $alerte?->fin_validite?->format('d/m/Y H:i'),
                ],
            ];
        })->values();

        return Js::encode(['type' => 'FeatureCollection', 'features' => $features]);
    }
}