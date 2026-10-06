<?php

namespace App\Http\Controllers\Prevention;

use App\Models\FicheConseil;
use App\Models\GesteCle;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Consultation des conseils publiés (citoyens et agents).
 */
class FicheConseilController extends PreventionController
{
    public function index(Request $request): View
    {
        $filtres = $this->filtres($request, ['recherche', 'thematique']);

        $fiches = FicheConseil::publiees()
            ->when($filtres['recherche'] ?? null, fn ($query, $recherche) => $query->where('titre', 'like', "%{$recherche}%"))
            ->when(isset(FicheConseil::THEMATIQUES[$filtres['thematique'] ?? '']), fn ($query) => $query->where('thematique', $filtres['thematique']))
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('prevention.citoyen.fiches.index', [
            'espace' => $this->espace($request),
            'fiches' => $fiches,
            'filtres' => $filtres,
        ]);
    }

    public function show(Request $request, FicheConseil $fiche): View
    {
        // Une fiche en brouillon n'est visible que par l'administrateur.
        abort_unless($fiche->estPubliee() || $request->user()->role === 'admin', 404);

        $fiche->load('gestes');

        return view('prevention.citoyen.fiches.show', [
            'espace' => $this->espace($request),
            'fiche' => $fiche,
            'gestesParMoment' => collect(GesteCle::MOMENTS)->map(fn ($libelle, $moment) => $fiche->gestes->where('moment', $moment)),
            'reponses' => $fiche->questions()->where('statut', 'repondue')->latest()->get(),
        ]);
    }
}
