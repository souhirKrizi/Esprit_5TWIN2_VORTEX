<?php

namespace App\Http\Controllers\Prevention\Admin;

use App\Http\Requests\Prevention\AjoutGestesIaRequest;
use App\Http\Requests\Prevention\GesteCleRequest;
use App\Models\FicheConseil;
use App\Models\GesteCle;
use App\Services\Prevention\ConseilsIaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;
use RuntimeException;

/**
 * Gestes d'une fiche : ajout manuel ou à partir des propositions de l'IA, modification et suppression.
 */
class GesteCleController extends AdminPreventionController
{
    /** Les propositions restent en session : rien n'est enregistré avant le choix de l'administrateur. */
    public const SESSION_PROPOSITIONS = 'gestes_ia.';

    private const PROPOSITIONS_PAR_MINUTE = 5;

    private const ORDRE_MAX = 20;

    public function proposer(Request $request, FicheConseil $fiche, ConseilsIaService $service): RedirectResponse
    {
        $page = redirect()->to($this->panneauIa($fiche));
        $cle = 'gestes-ia:'.$request->user()->getKey();

        if (RateLimiter::tooManyAttempts($cle, self::PROPOSITIONS_PAR_MINUTE)) {
            return $page->with('error', 'Trop de demandes. Réessayez dans '.RateLimiter::availableIn($cle).' secondes.');
        }

        RateLimiter::hit($cle);

        try {
            $request->session()->put(self::SESSION_PROPOSITIONS.$fiche->getKey(), $service->proposerGestes($fiche));
        } catch (RuntimeException $exception) {
            return $page->with('error', $exception->getMessage());
        }

        return $page->with('success', 'Propositions prêtes : cochez les gestes à garder.');
    }

    public function ajouterProposes(AjoutGestesIaRequest $request, FicheConseil $fiche): RedirectResponse
    {
        $gestes = $request->validated('gestes');

        DB::transaction(function () use ($fiche, $gestes) {
            foreach ($gestes as $geste) {
                // Chaque geste se place à la suite de ceux du même moment.
                $dernier = GesteCle::where('fiche_conseil_id', $fiche->getKey())->where('moment', $geste['moment'])->max('ordre') ?? 0;

                $fiche->gestes()->create([
                    'intitule' => $geste['intitule'],
                    'moment' => $geste['moment'],
                    'ordre' => min($dernier + 1, self::ORDRE_MAX),
                ]);
            }
        });

        $request->session()->forget(self::SESSION_PROPOSITIONS.$fiche->getKey());
        $nombre = count($gestes);

        return redirect()->route('admin.prevention.fiches.show', $fiche)
            ->with('success', $nombre > 1 ? $nombre.' gestes ajoutés à la fiche.' : '1 geste ajouté à la fiche.');
    }

    public function ignorerProposes(Request $request, FicheConseil $fiche): RedirectResponse
    {
        $request->session()->forget(self::SESSION_PROPOSITIONS.$fiche->getKey());

        return redirect()->to($this->panneauIa($fiche))->with('success', 'Propositions ignorées.');
    }

    private function panneauIa(FicheConseil $fiche): string
    {
        return route('admin.prevention.fiches.show', $fiche).'#gestes-ia';
    }

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
