@extends('layouts.space', ['space' => 'admin'])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page">
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Administration / Conseils de prévention</p>
            <h1>Questions des habitants</h1>
            <p class="ha-muted">Répondez aux questions. Les réponses s’affichent sous le conseil concerné.</p>
        </div>
    </header>

    @include('prevention.partials.messages')

    <section class="ha-admin-panel">
        <form class="ha-user-filters" method="GET" action="{{ route('admin.prevention.questions.index') }}">
            <label class="ha-select-field"><span class="sr-only">Statut</span>
                <select name="statut">
                    <option value="">Toutes les questions</option>
                    @foreach(\App\Models\QuestionCitoyen::STATUTS as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(($filtres['statut'] ?? '') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </label>
            <button class="ha-secondary-button" type="submit">Filtrer</button>
        </form>

        <div class="ha-table-wrap">
            <table class="ha-users-table">
                <thead><tr><th>Question</th><th>Conseil</th><th>Habitant</th><th>Date</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($questions as $question)
                        <tr>
                            <td>{{ \Illuminate\Support\Str::limit($question->question, 80) }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($question->fiche->titre, 35) }}</td>
                            <td>{{ $question->auteur->name }}</td>
                            <td>{{ $question->created_at->format('d/m/Y') }}</td>
                            <td><span class="pv-badge pv-statut-{{ $question->statut }}">{{ $question->libelleStatut() }}</span></td>
                            <td>
                                <div class="ha-row-actions">
                                    <a href="{{ route('admin.prevention.questions.edit', $question) }}">{{ $question->statut === 'en_attente' ? 'Répondre' : 'Modifier la réponse' }}</a>
                                    <form class="pv-inline-form" method="POST" action="{{ route('admin.prevention.questions.destroy', $question) }}" data-confirm="Supprimer cette question ?">
                                        @csrf @method('DELETE')
                                        <button class="pv-link pv-link-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="ha-empty-state">Aucune question.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($questions->hasPages())<div class="ha-pagination">{{ $questions->links() }}</div>@endif
    </section>
</div>
@endsection
