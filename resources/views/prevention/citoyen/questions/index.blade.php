@extends('layouts.space', ['space' => $espace])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page">
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Conseils de prévention</p>
            <h1>Mes questions</h1>
            <p class="ha-muted">Vos questions et leurs réponses, regroupées par conseil.</p>
        </div>
        <a class="ha-secondary-button" href="{{ route('prevention.fiches.index') }}">Voir les conseils</a>
    </header>

    @include('prevention.partials.messages')

    @forelse($groupes as $questions)
        @php $fiche = $questions->first()->fiche; @endphp
        <section class="ha-admin-panel pv-box pv-groupe">
            <header class="pv-groupe-titre">
                <div>
                    <h2>{{ $fiche->titre }}</h2>
                    <span class="pv-badge">{{ $fiche->libelleThematique() }}</span>
                    <span class="pv-badge">{{ $questions->count() }} question{{ $questions->count() > 1 ? 's' : '' }}</span>
                </div>
                @if($fiche->estPubliee())
                    <a class="pv-link" href="{{ route('prevention.fiches.show', $fiche) }}">Voir le conseil →</a>
                @endif
            </header>

            @foreach($questions as $question)
                <div class="pv-qr">
                    <div class="pv-tags">
                        <span class="pv-badge pv-statut-{{ $question->statut }}">{{ $question->libelleStatut() }}</span>
                        <small>Posée le {{ $question->created_at->format('d/m/Y') }}</small>
                    </div>
                    <p class="pv-question">{{ $question->question }}</p>

                    @if($question->statut === 'repondue')
                        <div class="pv-reponse"><p><b>Réponse :</b> {!! nl2br(e($question->reponse)) !!}</p></div>
                    @else
                        <div class="ha-row-actions">
                            <a href="{{ route('prevention.questions.edit', $question) }}">Modifier</a>
                            <form class="pv-inline-form" method="POST" action="{{ route('prevention.questions.destroy', $question) }}" data-confirm="Supprimer cette question ?">
                                @csrf @method('DELETE')
                                <button class="pv-link pv-link-danger" type="submit">Supprimer</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </section>
    @empty
        <section class="ha-admin-panel pv-box">
            <p class="pv-info">Vous n’avez posé aucune question. Ouvrez un conseil pour poser la vôtre.</p>
        </section>
    @endforelse
</div>
@endsection
