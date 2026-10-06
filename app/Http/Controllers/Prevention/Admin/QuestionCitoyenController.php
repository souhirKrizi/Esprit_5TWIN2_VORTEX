<?php

namespace App\Http\Controllers\Prevention\Admin;

use App\Http\Requests\Prevention\ReponseQuestionRequest;
use App\Models\QuestionCitoyen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Questions des habitants : l'administrateur répond ou supprime.
 */
class QuestionCitoyenController extends AdminPreventionController
{
    public function index(Request $request): View
    {
        $filtres = $this->filtres($request, ['statut']);

        $questions = QuestionCitoyen::query()
            ->when(isset(QuestionCitoyen::STATUTS[$filtres['statut'] ?? '']), fn ($query) => $query->where('statut', $filtres['statut']))
            ->with(['fiche', 'auteur'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('prevention.admin.questions.index', compact('questions', 'filtres'));
    }

    public function edit(QuestionCitoyen $question): View
    {
        return view('prevention.admin.questions.edit', ['question' => $question->load(['fiche', 'auteur'])]);
    }

    public function update(ReponseQuestionRequest $request, QuestionCitoyen $question): RedirectResponse
    {
        $question->reponse = $request->validated('reponse');
        $question->statut = 'repondue';
        $question->save();

        return redirect()->route('admin.prevention.questions.index')->with('success', 'Réponse enregistrée.');
    }

    public function destroy(QuestionCitoyen $question): RedirectResponse
    {
        $question->delete();

        return redirect()->route('admin.prevention.questions.index')->with('success', 'Question supprimée.');
    }
}
