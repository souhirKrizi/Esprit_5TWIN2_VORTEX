<?php

namespace App\Http\Controllers\Prevention;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Outils partagés par les contrôleurs du module « Conseils de prévention ».
 */
abstract class PreventionController extends Controller
{
    /**
     * Espace (et donc sidebar) correspondant au rôle du compte connecté.
     */
    protected function espace(Request $request): string
    {
        return match ($request->user()->role) {
            'admin' => 'admin',
            'agent-campagne' => 'agent-campagne',
            default => 'user',
        };
    }

    /**
     * Ne garde que les filtres textuels non vides de la requête.
     *
     * @param  list<string>  $cles
     * @return array<string, string>
     */
    protected function filtres(Request $request, array $cles): array
    {
        $filtres = [];
        foreach ($cles as $cle) {
            $valeur = $request->query($cle);
            if (is_string($valeur) && trim($valeur) !== '') {
                $filtres[$cle] = mb_substr(trim($valeur), 0, 100);
            }
        }

        return $filtres;
    }
}
