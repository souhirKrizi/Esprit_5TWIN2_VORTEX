<?php

namespace App\Http\Controllers;

use App\Http\Requests\VerificationDoublonRequest;
use App\Services\DetectionDoublonService;
use Illuminate\Http\JsonResponse;

/**
 * Vérifie si un signalement en cours de saisie est géographiquement similaire
 * à un signalement existant, afin d'avertir le citoyen (sans blocage).
 *
 * Renvoie UNIQUEMENT le nombre de similaires — aucune donnée personnelle.
 */
class VerificationDoublonController extends Controller
{
    public function __invoke(
        VerificationDoublonRequest $request,
        DetectionDoublonService    $service,
    ): JsonResponse {
        abort_unless($request->user()?->role === 'user', 403);

        $similaires = $service->trouverSimilaires(
            lat:             (float) $request->input('latitude'),
            lng:             (float) $request->input('longitude'),
            typeSignalement: (string) $request->input('type_signalement'),
            typeIncident:    $request->input('type_incident'),
        );

        $count = $similaires->count();

        $message = match (true) {
            $count === 0 => '',
            $count === 1 => 'Un incident similaire a déjà été signalé à moins de 200 m. Votre signalement sera quand même enregistré.',
            default      => "{$count} incidents similaires ont déjà été signalés à moins de 200 m. Votre signalement sera quand même enregistré.",
        };

        return response()->json([
            'similaires' => $count,
            'message'    => $message,
        ]);
    }
}

