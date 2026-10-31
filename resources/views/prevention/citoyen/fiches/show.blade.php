@extends('layouts.space', ['space' => $espace])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page">
    <a class="ha-back-link" href="{{ route('prevention.fiches.index') }}">← Retour aux conseils</a>
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">{{ $fiche->libelleThematique() }}</p>
            <h1>{{ $fiche->titre }}</h1>
            <div class="pv-tags" style="margin-top:12px">
                <span class="pv-badge pv-niveau-{{ $fiche->niveau_alerte }}">{{ $fiche->libelleAlerte() }}</span>
                @if($fiche->type_equipement)<span class="pv-badge">{{ $fiche->libelleEquipement() }}</span>@endif
                @unless($fiche->estPubliee())<span class="pv-badge pv-statut-brouillon">Brouillon (visible par l’admin seulement)</span>@endunless
            </div>
        </div>
    </header>

    @include('prevention.partials.messages')

    <section class="ha-admin-panel pv-box pv-text">
        @if($fiche->image)
            <img class="pv-image" src="{{ $fiche->imageUrl() }}" alt="{{ $fiche->titre }}">
        @endif
        <p><strong>{{ $fiche->resume }}</strong></p>
        @foreach(preg_split('/\R{2,}/', trim($fiche->contenu)) as $paragraphe)
            <p>{!! nl2br(e($paragraphe)) !!}</p>
        @endforeach
    </section>

    @if($fiche->gestes->isNotEmpty())
        <section class="ha-admin-panel pv-box">
            <h2>Les gestes à faire</h2>
            <div class="pv-moments">
                @foreach($gestesParMoment as $moment => $gestes)
                    <div class="pv-moment">
                        <h3>{{ \App\Models\GesteCle::MOMENTS[$moment] }}</h3>
                        @if($gestes->isEmpty())
                            <p class="pv-vide">Rien de particulier.</p>
                        @else
                            <ol>
                                @foreach($gestes as $geste)
                                    <li>{{ $geste->intitule }}</li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="ha-admin-panel pv-box">
        <h2>Questions des habitants</h2>
        @forelse($reponses as $item)
            <div class="pv-qr">
                <strong>{{ $item->question }}</strong>
                <div class="pv-reponse"><p style="margin:0">{!! nl2br(e($item->reponse)) !!}</p></div>
            </div>
        @empty
            <p class="pv-info">Aucune question avec réponse pour le moment.</p>
        @endforelse
    </section>

    @if($fiche->estPubliee())
        <section class="ha-admin-panel pv-box">
            <h2>Poser une question</h2>
            <form class="pv-form" method="POST" action="{{ route('prevention.questions.store', $fiche) }}" novalidate>
                @csrf
                <div class="pv-field">
                    <label for="question">Votre question <span>*</span></label>
                    <textarea id="question" name="question" rows="3" maxlength="500" class="@error('question') is-invalid @enderror" placeholder="Exemple : combien de temps le frigo garde-t-il le froid pendant une coupure ?">{{ old('question') }}</textarea>
                    @error('question')<p class="pv-erreur">{{ $message }}</p>@enderror
                    <small>Entre 10 et 500 caractères.</small>
                </div>
                <div class="pv-actions"><button class="ha-primary-button" type="submit">Envoyer</button></div>
            </form>
        </section>
    @endif
</div>
@endsection
