<?php

namespace App\Http\Controllers;

use App\Models\RessourceUtile;
use App\Models\Signalement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarteController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureCitizen($request);

        return view('signalements.carte', ['space' => 'user']);
    }

    public function ressources(Request $request): JsonResponse
    {
        $this->ensureCitizen($request);

        $ressources = RessourceUtile::query()
            ->when($request->filled('gouvernorat'), fn ($query) => $query->where('gouvernorat', $request->string('gouvernorat')->toString()))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')->toString()))
            ->when($request->filled('q'), function ($query) use ($request): void {
                $search = $request->string('q')->trim()->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('nom', 'like', "%{$search}%")
                        ->orWhere('adresse', 'like', "%{$search}%")
                        ->orWhere('gouvernorat', 'like', "%{$search}%");
                });
            })
            ->when($request->input('etat') === 'rouge', fn ($query) => $query->whereHas('signalements', fn ($signalements) => $signalements->incidentActif()))
            ->when($request->input('etat') === 'vert', fn ($query) => $query->whereDoesntHave('signalements', fn ($signalements) => $signalements->incidentActif()))
            ->withCount(['signalements as incidents_actifs_count' => fn ($query) => $query->incidentActif()])
            ->orderBy('gouvernorat')
            ->orderBy('nom')
            ->get()
            ->map(fn (RessourceUtile $ressource): array => [
                'id' => $ressource->id,
                'nom' => $ressource->nom,
                'type' => $ressource->type,
                'gouvernorat' => $ressource->gouvernorat,
                'adresse' => $ressource->adresse,
                'latitude' => (float) $ressource->latitude,
                'longitude' => (float) $ressource->longitude,
                'statut' => $ressource->statut,
                'etat' => $ressource->etat,
                'incidents_actifs' => $ressource->incidents_actifs_count,
                'url' => route('user.ressources.show', $ressource),
            ]);

        return response()->json([
            'data' => $ressources,
            'compteurs' => [
                'incidents_actifs' => $ressources->where('etat', 'rouge')->count(),
                'ressources_ok' => $ressources->where('etat', 'vert')->count(),
            ],
        ]);
    }

    private function ensureCitizen(Request $request): void
    {
        abort_unless($request->user()?->role === 'user', 403);
    }
}
