<?php

namespace App\Http\Controllers;

use App\Models\RessourceUtile;
use App\Models\Signalement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSignalementController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdmin($request);

        $signalements = Signalement::query()
            ->with(['user', 'ressourceUtile', 'piecesJointes'])
            ->when($request->filled('ressource'), fn ($query) => $query->where('ressource_utile_id', $request->integer('ressource')))
            ->when($request->boolean('pieces_jointes'), fn ($query) => $query->whereHas('piecesJointes'))
            ->when($request->filled('statut'), fn ($query) => $query->where('statut_validation', $request->string('statut')->toString()))
            ->when($request->filled('type'), fn ($query) => $query->where('type_signalement', $request->string('type')->toString()))
            ->when($request->filled('suspicion'), function ($query) use ($request): void {
                $query->where('niveau_suspicion', $request->string('suspicion')->toString());
            })
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->toString();
                $query->where(function ($query) use ($search): void {
                    $query->where('commentaire', 'like', "%{$search}%")
                        ->orWhere('type_incident', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->input('tri') === 'suspicion', fn ($query) => $query->orderByDesc('score_suspicion'))
            ->when($request->input('tri') !== 'suspicion', fn ($query) => $query->latest())
            ->paginate(12)
            ->withQueryString();

        return view('admin.signalements.index', ['signalements' => $signalements, 'space' => 'admin']);
    }

    public function carte(Request $request): View
    {
        $this->ensureAdmin($request);

        return view('admin.signalements.carte', ['space' => 'admin']);
    }

    public function ressources(Request $request): JsonResponse
    {
        $this->ensureAdmin($request);

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
                'url' => route('admin.signalements.index', ['ressource' => $ressource->id]),
            ]);
        $signalements = Signalement::query()
            ->incidentActif()
            ->with('piecesJointes')
            ->get()
            ->map(fn (Signalement $signalement): array => [
                'id' => $signalement->id,
                'latitude' => (float) $signalement->latitude,
                'longitude' => (float) $signalement->longitude,
                'commentaire' => $signalement->commentaire,
                'type_incident' => $signalement->type_incident,
                'url' => route('admin.signalements.show', $signalement),
            ]);

        return response()->json([
            'data' => $ressources,
            'signalements' => $signalements,
            'compteurs' => [
                'incidents_actifs' => $ressources->where('etat', 'rouge')->count(),
                'ressources_ok' => $ressources->where('etat', 'vert')->count(),
            ],
        ]);
    }

    public function show(Request $request, Signalement $signalement): View
    {
        $this->ensureAdmin($request);
        $signalement->load(['user', 'ressourceUtile', 'piecesJointes', 'doublonDe']);

        return view('admin.signalements.show', ['signalement' => $signalement, 'space' => 'admin']);
    }

    public function validateSignalement(Request $request, Signalement $signalement): RedirectResponse
    {
        $this->ensureAdmin($request);
        abort_unless($signalement->statut_validation === Signalement::STATUT_EN_ATTENTE, 422, 'Ce signalement a déjà été traité.');

        $signalement->update([
            'statut_validation' => Signalement::STATUT_VALIDE,
            'motif_rejet' => null,
        ]);

        return back()->with('success', 'Le signalement a été validé.');
    }

    public function reject(Request $request, Signalement $signalement): RedirectResponse
    {
        $this->ensureAdmin($request);
        abort_unless($signalement->statut_validation === Signalement::STATUT_EN_ATTENTE, 422, 'Ce signalement a déjà été traité.');

        $validated = $request->validate([
            'motif_rejet' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'motif_rejet.required' => 'Le motif du rejet est obligatoire.',
            'motif_rejet.min' => 'Le motif du rejet doit contenir au moins 10 caractères.',
            'motif_rejet.max' => 'Le motif du rejet ne peut pas dépasser 1000 caractères.',
        ], [
            'motif_rejet' => 'motif du rejet',
        ]);

        $signalement->update([
            'statut_validation' => Signalement::STATUT_REJETE,
            'motif_rejet' => trim(strip_tags($validated['motif_rejet'])),
        ]);

        return back()->with('success', 'Le signalement a été rejeté.');
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }
}
