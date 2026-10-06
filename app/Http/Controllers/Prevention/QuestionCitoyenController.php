<?php

namespace App\Http\Controllers\Prevention;

use App\Http\Requests\Prevention\QuestionCitoyenRequest;
use App\Models\FicheConseil;
use App\Models\QuestionCitoyen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * « Mes questions » : le citoyen pose une question sur un conseil et suit la réponse.
 */
class QuestionCitoyenController extends PreventionController
{
    public function index(Request $request): View
    {
        // Questions regroupées par conseil : le conseil le plus récemment questionné apparaît en premier.
        $groupes = QuestionCitoyen::where('user_id', $request->user()->getKey())
            ->with('fiche')
            ->latest()
            ->latest('id')
            ->get()
            ->groupBy('fiche_conseil_id');

        return view('prevention.citoyen.questions.index', [
            'espace' => $this->espace($request),
            'groupes' => $groupes,
        ]);
    }

    public function store(QuestionCitoyenRequest $request, FicheConseil $fiche): RedirectResponse
    {
        abort_unless($fiche->estPubliee(), 404);

        $question = new QuestionCitoyen($request->validated());
        $question->fiche_conseil_id = $fiche->getKey();
        $question->user_id = $request->user()->getKey();
        $question->statut = 'en_attente';
        $question->save();

        return redirect()->route('prevention.fiches.show', $fiche)->with('success', 'Votre question a été envoyée. Vous verrez la réponse dans « Mes questions ».');
    }

    public function edit(Request $request, QuestionCitoyen $question): View|RedirectResponse
    {
        abort_unless($question->appartientA($request->user()), 403);

        if (! $question->estModifiable()) {
            return redirect()->route('prevention.questions.index')->with('error', 'Cette question a déjà une réponse, elle ne peut plus être modifiée.');
        }

        return view('prevention.citoyen.questions.edit', [
            'espace' => $this->espace($request),
            'question' => $question,
        ]);
    }

    public function update(QuestionCitoyenRequest $request, QuestionCitoyen $question): RedirectResponse
    {
        if (! $question->estModifiable()) {
            return redirect()->route('prevention.questions.index')->with('error', 'Cette question a déjà une réponse, elle ne peut plus être modifiée.');
        }

        $question->update($request->validated());

        return redirect()->route('prevention.questions.index')->with('success', 'Question modifiée.');
    }

    public function destroy(Request $request, QuestionCitoyen $question): RedirectResponse
    {
        abort_unless($question->appartientA($request->user()), 403);

        if (! $question->estModifiable()) {
            return redirect()->route('prevention.questions.index')->with('error', 'Cette question a déjà une réponse, elle ne peut plus être supprimée.');
        }

        $question->delete();

        return redirect()->route('prevention.questions.index')->with('success', 'Question supprimée.');
    }
}
