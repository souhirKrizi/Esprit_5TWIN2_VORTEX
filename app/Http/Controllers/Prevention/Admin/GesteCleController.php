<?php

namespace App\Http\Controllers\Prevention\Admin;

use App\Http\Requests\Prevention\GesteCleRequest;
use App\Models\FicheConseil;
use App\Models\GesteCle;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Gestes d'une fiche : ajout depuis la page de la fiche, modification et suppression.
 */
class GesteCleController extends AdminPreventionController
{
    public function store(GesteCleRequest $request, FicheConseil $fiche): RedirectResponse
    {
        $fiche->gestes()->create($request->validated());

        return redirect()->route('admin.prevention.fiches.show', $fiche)->with('success', 'Geste ajouté.');
    }

    public function edit(GesteCle $geste): View
    {
        return view('prevention.admin.gestes.edit', ['geste' => $geste->load('fiche')]);
    }

    public function update(GesteCleRequest $request, GesteCle $geste): RedirectResponse
    {
        $geste->update($request->validated());

        return redirect()->route('admin.prevention.fiches.show', $geste->fiche_conseil_id)->with('success', 'Geste modifié.');
    }

    public function destroy(GesteCle $geste): RedirectResponse
    {
        $geste->delete();

        return redirect()->route('admin.prevention.fiches.show', $geste->fiche_conseil_id)->with('success', 'Geste supprimé.');
    }
}
