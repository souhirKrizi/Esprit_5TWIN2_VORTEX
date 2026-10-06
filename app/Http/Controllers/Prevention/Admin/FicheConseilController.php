<?php

namespace App\Http\Controllers\Prevention\Admin;

use App\Http\Requests\Prevention\FicheConseilRequest;
use App\Models\FicheConseil;
use App\Models\GesteCle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        return view('prevention.admin.fiches.index', compact('fiches', 'filtres'));
    }

    public function create(): View
    {
        return view('prevention.admin.fiches.create', [
            'fiche' => new FicheConseil(['statut' => 'brouillon']),
        ]);
    }

    public function store(FicheConseilRequest $request): RedirectResponse
    {
        $fiche = FicheConseil::create($request->validated());

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
        $fiche->update($request->validated());

        return redirect()->route('admin.prevention.fiches.show', $fiche)->with('success', 'Fiche modifiée.');
    }

    public function destroy(FicheConseil $fiche): RedirectResponse
    {
        $fiche->delete();

        return redirect()->route('admin.prevention.fiches.index')->with('success', 'Fiche supprimée.');
    }
}
