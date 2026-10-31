<?php

namespace App\Services;

use App\Models\Signalement;
use Illuminate\Support\Collection;

/**
 * Couche 1 : Détection des doublons géographiques.
 *
 * Utilise un filtre bounding-box SQL puis un calcul Haversine exact en PHP
 * (compatible SQLite en mémoire — pas de fonctions trigonométriques SQL).
 */
class DetectionDoublonService
{
    /**
     * Trouve les signalements similaires dans le rayon et la fenêtre configurés.
     *
     * @param  float        $lat              Latitude du nouveau signalement
     * @param  float        $lng              Longitude du nouveau signalement
     * @param  string       $typeSignalement  'incident' | 'ressource_ok'
     * @param  string|null  $typeIncident     Sous-type si incident
     * @param  int|null     $excludeId        ID à exclure (modification d'un existant)
     *
     * @return Collection<int, Signalement>  Triée du plus proche au plus lointain
     */
    public function trouverSimilaires(
        float $lat,
        float $lng,
        string $typeSignalement,
        ?string $typeIncident,
        ?int $excludeId = null,
    ): Collection {
        $rayonM     = (int) config('assistant_ia.doublons_rayon_m', 200);
        $fenetreH   = (int) config('assistant_ia.doublons_fenetre_heures', 48);

        // Deltas pour le bounding-box pré-filtre SQL (approximatif mais rapide)
        $deltaLat = $rayonM / 111320;
        $deltaLng = $rayonM / (111320 * cos(deg2rad($lat)));

        $query = Signalement::query()
            ->whereBetween('latitude',  [$lat - $deltaLat, $lat + $deltaLat])
            ->whereBetween('longitude', [$lng - $deltaLng, $lng + $deltaLng])
            ->where('type_signalement', $typeSignalement)
            ->whereIn('statut_validation', [Signalement::STATUT_EN_ATTENTE, Signalement::STATUT_VALIDE])
            ->where('created_at', '>=', now()->subHours($fenetreH));

        // Pour les incidents : même sous-type uniquement
        if ($typeSignalement === Signalement::TYPE_INCIDENT && $typeIncident !== null) {
            $query->where('type_incident', $typeIncident);
        }

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        $candidats = $query->get();

        // Filtre Haversine exact en PHP (distance réelle ≤ rayon)
        return $candidats
            ->filter(fn (Signalement $s): bool => $this->haversineM($lat, $lng, (float) $s->latitude, (float) $s->longitude) <= $rayonM)
            ->sortBy(fn (Signalement $s): float => $this->haversineM($lat, $lng, (float) $s->latitude, (float) $s->longitude))
            ->values();
    }

    /**
     * Calcule la distance en mètres entre deux coordonnées GPS (formule Haversine).
     */
    public function haversineM(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R     = 6371000; // rayon de la Terre en mètres
        $dLat  = deg2rad($lat2 - $lat1);
        $dLng  = deg2rad($lng2 - $lng1);
        $a     = sin($dLat / 2) ** 2
               + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}

