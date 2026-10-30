<?php

namespace App\Http\Controllers\Prevention\Admin;

use App\Http\Requests\Prevention\AlerteEnCoursRequest;
use App\Http\Requests\Prevention\FicheConseilRequest;
use App\Models\FicheConseil;
use App\Models\GesteCle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Administration des fiches conseils (CRUD).
 */
class FicheConseilController extends AdminPreventionController
{
    public function index(Request $request): View
    {
        $filtres = $this->filtres($request, ['recherche', 'statut']);

        $fiches = FicheConseil::query()
            ->when($filtres['recherche'] ?? null, fn ($query, $recherche) => $query->where('titre', 'like', "%{$recherche}%"))
            ->when(isset(FicheConseil::STATUTS[$filtres['statut'] ?? '']), fn ($query) => $query->where('statut', $filtres['statut']))
            ->withCount('gestes')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('prevention.admin.fiches.index', [
            'fiches' => $fiches,
            'filtres' => $filtres,
            'alerteEnCours' => FicheConseil::niveauAlerteEnCours(),
        ]);
    }

    public function changerAlerte(AlerteEnCoursRequest $request): RedirectResponse
    {
        FicheConseil::definirNiveauAlerteEnCours($request->validated('niveau'));

        return redirect()->route('admin.prevention.fiches.index')->with('success', 'Alerte en cours mise à jour.');
    }

    public function create(): View
    {
        return view('prevention.admin.fiches.create', [
            'fiche' => new FicheConseil(['statut' => 'brouillon']),
        ]);
    }

    public function store(FicheConseilRequest $request): RedirectResponse
    {
        $fiche = FicheConseil::create($this->donnees($request));

        return redirect()->route('admin.prevention.fiches.show', $fiche)->with('success', 'Fiche ajoutée. Vous pouvez maintenant ajouter ses gestes.');
    }

    public function show(FicheConseil $fiche): View
    {
        return view('prevention.admin.fiches.show', [
            'fiche' => $fiche->load('gestes'),
            'nouveauGeste' => new GesteCle(['moment' => 'avant', 'ordre' => $fiche->gestes->count() + 1]),
        ]);
    }

    public function edit(FicheConseil $fiche): View
    {
        return view('prevention.admin.fiches.edit', compact('fiche'));
    }

    public function update(FicheConseilRequest $request, FicheConseil $fiche): RedirectResponse
    {
        $fiche->update($this->donnees($request, $fiche));

        return redirect()->route('admin.prevention.fiches.show', $fiche)->with('success', 'Fiche modifiée.');
    }

    public function destroy(FicheConseil $fiche): RedirectResponse
    {
        $fiche->delete();

        if ($fiche->image) {
            Storage::disk('public')->delete($fiche->image);
        }

        return redirect()->route('admin.prevention.fiches.index')->with('success', 'Fiche supprimée.');
    }

    /**
     * Données validées de la fiche : enregistre la nouvelle image et supprime l'ancienne si besoin.
     */
    private function donnees(FicheConseilRequest $request, ?FicheConseil $fiche = null): array
    {
        $donnees = $request->safe()->except(['image', 'supprimer_image']);

        if ($request->hasFile('image') || $request->boolean('supprimer_image')) {
            if ($fiche?->image) {
                Storage::disk('public')->delete($fiche->image);
            }

            $donnees['image'] = $request->hasFile('image') ? $request->file('image')->store('fiches', 'public') : null;
        }

        return $donnees;
    }
}
