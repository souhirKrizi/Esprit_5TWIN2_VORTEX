<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSignalementRequest;
use App\Http\Requests\UpdateSignalementRequest;
use App\Jobs\AnalyserSignalementJob;
use App\Models\RessourceUtile;
use App\Models\Signalement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SignalementController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureCitizen($request);

        $signalements = Signalement::query()
            ->where('user_id', $request->user()->id)
            ->with(['ressourceUtile', 'piecesJointes'])
            ->when($request->filled('statut'), fn ($query) => $query->where('statut_validation', $request->string('statut')->toString()))
            ->when($request->filled('type'), fn ($query) => $query->where('type_signalement', $request->string('type')->toString()))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('signalements.index', compact('signalements'));
    }

    public function publicIndex(Request $request): View
    {
        $this->ensureCitizen($request);

        $signalements = Signalement::query()
            ->where('user_id', '!=', $request->user()->id)
            ->valide()
            ->with(['ressourceUtile', 'piecesJointes'])
            ->when($request->filled('type'), fn ($query) => $query->where('type_signalement', $request->string('type')->toString()))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('signalements.public-index', ['signalements' => $signalements, 'space' => 'user']);
    }

    public function publicShow(Request $request, Signalement $signalement): View
    {
        $this->ensureCitizen($request);
        abort_unless($signalement->statut_validation === Signalement::STATUT_VALIDE && $signalement->user_id !== $request->user()->id, 404);
        $signalement->load(['ressourceUtile', 'piecesJointes']);

        return view('signalements.public-show', ['signalement' => $signalement, 'space' => 'user']);
    }

    public function create(Request $request): View
    {
        $this->ensureCitizen($request);

        $ressources = RessourceUtile::query()->actif()->orderBy('gouvernorat')->orderBy('nom')->get();

        return view('signalements.form', [
            'space' => 'user',
            'signalement' => new Signalement(['ressource_utile_id' => $request->integer('ressource') ?: null]),
            'ressources' => $ressources,
        ]);
    }

    public function store(StoreSignalementRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->ensureNotDuplicate($request, $data);
        unset($data['images']);

        $signalement = DB::transaction(function () use ($request, $data): Signalement {
            $signalement = $request->user()->signalements()->create($data);
            $this->storeImages($request, $signalement);

            return $signalement;
        });

        try {
            AnalyserSignalementJob::dispatch($signalement->id)->afterResponse();
        } catch (\Throwable $e) {
            Log::warning('Détection: impossible de dispatcher le job', ['id' => $signalement->id]);
        }

        return redirect()->route('user.signalements.show', $signalement)
            ->with('success', 'Signalement envoyé, en attente de validation.');
    }

    public function show(Signalement $signalement): View
    {
        abort_unless(request()->user()?->role === 'user', 403);
        Gate::authorize('view', $signalement);
        $signalement->load(['ressourceUtile', 'piecesJointes']);

        return view('signalements.show', ['signalement' => $signalement, 'space' => 'user']);
    }

    public function edit(Signalement $signalement): View
    {
        abort_unless(request()->user()?->role === 'user', 403);
        Gate::authorize('update', $signalement);
        $signalement->load('piecesJointes');
        $ressources = RessourceUtile::query()->actif()->orderBy('gouvernorat')->orderBy('nom')->get();

        return view('signalements.form', ['signalement' => $signalement, 'ressources' => $ressources, 'space' => 'user']);
    }

    public function update(UpdateSignalementRequest $request, Signalement $signalement): RedirectResponse
    {
        Gate::authorize('update', $signalement);
        $data = $request->validated();
        unset($data['images']);
        $newImages = count($request->file('images', []));

        if ($signalement->piecesJointes()->count() + $newImages > 5) {
            throw ValidationException::withMessages([
                'images' => 'Le nombre total de pièces jointes ne peut pas dépasser 5.',
            ]);
        }

        $signalement->update($data + ['motif_rejet' => null]);
        $this->storeImages($request, $signalement);

        if ($signalement->wasChanged('commentaire')) {
            try {
                AnalyserSignalementJob::dispatch($signalement->id)->afterResponse();
            } catch (\Throwable $e) {
                Log::warning('Détection: impossible de dispatcher le job update', ['id' => $signalement->id]);
            }
        }

        return redirect()->route('user.signalements.show', $signalement)
            ->with('success', 'Signalement modifié avec succès.');
    }

    public function destroy(Signalement $signalement): RedirectResponse
    {
        abort_unless(request()->user()?->role === 'user', 403);
        Gate::authorize('delete', $signalement);
        $signalement->delete();

        return redirect()->route('user.signalements.index')->with('success', 'Signalement supprimé.');
    }

    private function ensureNotDuplicate(Request $request, array $data): void
    {
        $duplicate = $request->user()->signalements()
            ->where('type_signalement', $data['type_signalement'])
            ->where('latitude', $data['latitude'])
            ->where('longitude', $data['longitude'])
            ->where('created_at', '>=', now()->subMinutes(5))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'type_signalement' => 'Un signalement identique a déjà été envoyé récemment. Veuillez patienter quelques minutes.',
            ]);
        }
    }

    private function storeImages(Request $request, Signalement $signalement): void
    {
        foreach ($request->file('images', []) as $image) {
            $path = $image->store('signalements', 'public');
            $signalement->piecesJointes()->create([
                'chemin_fichier' => $path,
                'nom_original' => Str::limit($image->getClientOriginalName(), 255, ''),
                'type_mime' => $image->getMimeType() ?: $image->getClientMimeType(),
                'taille' => $image->getSize(),
            ]);
        }
    }

    private function ensureCitizen(Request $request): void
    {
        abort_unless($request->user()?->role === 'user', 403);
    }
}
