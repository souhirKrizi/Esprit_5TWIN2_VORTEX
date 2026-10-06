<?php

namespace App\Http\Controllers\Prevention;

use App\Http\Requests\Prevention\EquipementSensibleRequest;
use App\Models\EquipementSensible;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * « Mes équipements » : le citoyen gère ses appareils sensibles et décrit leur problème.
 */
class EquipementSensibleController extends PreventionController
{
    public function index(Request $request): View
    {
        return view('prevention.citoyen.equipements.index', [
            'espace' => $this->espace($request),
            'equipements' => EquipementSensible::where('user_id', $request->user()->getKey())->orderBy('nom')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('prevention.citoyen.equipements.create', [
            'espace' => $this->espace($request),
            'equipement' => new EquipementSensible,
        ]);
    }

    public function store(EquipementSensibleRequest $request): RedirectResponse
    {
        $equipement = new EquipementSensible($request->validated());
        $equipement->user_id = $request->user()->getKey();
        $equipement->save();

        return redirect()->route('prevention.equipements.show', $equipement)->with('success', 'Équipement ajouté.');
    }

    public function show(Request $request, EquipementSensible $equipement): View
    {
        abort_unless($equipement->appartientA($request->user()), 403);

        return view('prevention.citoyen.equipements.show', [
            'espace' => $this->espace($request),
            'equipement' => $equipement,
        ]);
    }

    public function edit(Request $request, EquipementSensible $equipement): View
    {
        abort_unless($equipement->appartientA($request->user()), 403);

        return view('prevention.citoyen.equipements.edit', [
            'espace' => $this->espace($request),
            'equipement' => $equipement,
        ]);
    }

    public function update(EquipementSensibleRequest $request, EquipementSensible $equipement): RedirectResponse
    {
        $equipement->update($request->validated());

        return redirect()->route('prevention.equipements.show', $equipement)->with('success', 'Équipement modifié.');
    }

    public function destroy(Request $request, EquipementSensible $equipement): RedirectResponse
    {
        abort_unless($equipement->appartientA($request->user()), 403);

        $equipement->delete();

        return redirect()->route('prevention.equipements.index')->with('success', 'Équipement supprimé.');
    }
}
