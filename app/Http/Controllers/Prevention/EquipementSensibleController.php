<?php

namespace App\Http\Controllers\Prevention;

use App\Http\Requests\Prevention\EquipementSensibleRequest;
use App\Models\EquipementSensible;
use App\Services\Prevention\ConseilsIaService;
use App\Services\Prevention\DescriptionNonSignificative;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use RuntimeException;

/**
 * « Mes équipements » : le citoyen gère ses appareils sensibles, décrit leur problème et obtient des conseils de l'IA.
 */
class EquipementSensibleController extends PreventionController
{
    private const GENERATIONS_PAR_MINUTE = 5;

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
        $equipement->fill($request->validated());
        $conseilsObsoletes = $equipement->isDirty() && $equipement->conseils_ia !== null;

        // Les conseils décrivaient l'ancien équipement : on les retire pour ne pas induire en erreur.
        if ($conseilsObsoletes) {
            $equipement->conseils_ia = null;
            $equipement->conseils_ia_le = null;
        }

        $equipement->save();

        return redirect()->route('prevention.equipements.show', $equipement)->with(
            'success',
            $conseilsObsoletes ? 'Équipement modifié. Générez de nouveaux conseils adaptés à ces changements.' : 'Équipement modifié.'
        );
    }

    public function genererConseils(Request $request, EquipementSensible $equipement, ConseilsIaService $service): RedirectResponse
    {
        abort_unless($equipement->appartientA($request->user()), 403);

        $page = redirect()->route('prevention.equipements.show', $equipement);
        $cle = 'conseils-ia:'.$request->user()->getKey();

        if (RateLimiter::tooManyAttempts($cle, self::GENERATIONS_PAR_MINUTE)) {
            return $page->with('error', 'Trop de demandes. Réessayez dans '.RateLimiter::availableIn($cle).' secondes.');
        }

        RateLimiter::hit($cle);

        try {
            $equipement->conseils_ia = $service->generer($equipement);
        } catch (DescriptionNonSignificative $exception) {
            // Les anciens conseils reposaient sur cette même description : on les retire.
            $equipement->conseils_ia = null;
            $equipement->conseils_ia_le = null;
            $equipement->save();

            return $page->with('error', $exception->getMessage());
        } catch (RuntimeException $exception) {
            return $page->with('error', $exception->getMessage());
        }

        $equipement->conseils_ia_le = now();
        $equipement->save();

        return $page->with('success', 'Vos conseils personnalisés sont prêts.');
    }

    public function destroy(Request $request, EquipementSensible $equipement): RedirectResponse
    {
        abort_unless($equipement->appartientA($request->user()), 403);

        $equipement->delete();

        return redirect()->route('prevention.equipements.index')->with('success', 'Équipement supprimé.');
    }
}
