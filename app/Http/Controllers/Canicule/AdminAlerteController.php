<?php

namespace App\Http\Controllers\Canicule;

use App\Models\Alerte;
use App\Models\Zone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAlerteController extends AdminCaniculeController
{
    public function index(): View
    {
        return view('admin.canicule.alertes.index', ['alertes' => Alerte::with('zone')->latest()->paginate(15)]);
    }

    public function create(): View
    {
        return view('admin.canicule.alertes.form', ['alerte' => new Alerte(['statut' => 'brouillon']), 'zones' => Zone::where('active', true)->orderBy('nom')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Alerte::create($this->validated($request) + ['created_by' => $request->user()->id, 'origine' => 'manuelle']);

        return redirect()->route('admin.canicule.alertes.index')->with('success', 'Alerte créée.');
    }

    public function edit(Alerte $alerte): View
    {
        return view('admin.canicule.alertes.form', ['alerte' => $alerte, 'zones' => Zone::where('active', true)->orWhere('id', $alerte->zone_id)->orderBy('nom')->get()]);
    }

    public function update(Request $request, Alerte $alerte): RedirectResponse
    {
        $alerte->update($this->validated($request));

        return redirect()->route('admin.canicule.alertes.index')->with('success', 'Alerte modifiée.');
    }

    public function destroy(Alerte $alerte): RedirectResponse
    {
        $alerte->delete();

        return redirect()->route('admin.canicule.alertes.index')->with('success', 'Alerte supprimée.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'titre' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string', 'max:5000'],
            'niveau_gravite' => ['required', 'in:jaune,orange,rouge'],
            'debut_validite' => ['required', 'date'],
            'fin_validite' => ['required', 'date', 'after_or_equal:debut_validite'],
            'statut' => ['required', 'in:brouillon,active,terminee,annulee'],
        ]);
    }
}