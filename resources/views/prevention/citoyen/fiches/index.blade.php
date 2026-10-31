@extends('layouts.space', ['space' => $espace])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page">
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Conseils de prévention</p>
            <h1>Conseils</h1>
            <p class="ha-muted">Les bons gestes face à la canicule et aux coupures de courant.</p>
        </div>
    </header>

    @include('prevention.partials.messages')

    @if($alerteEnCours && ! $filtres && $fiches->onFirstPage())
        @php
            $niveaux = array_keys(\App\Models\FicheConseil::NIVEAUX);
            $positionAlerte = array_search($alerteEnCours, $niveaux, true);
        @endphp
        <section class="pv-alerte pv-alerte-{{ $alerteEnCours }}">
            <div class="pv-alerte-tete">
                <span class="pv-alerte-icone"><x-space-icon name="alert" /></span>
                <div class="pv-alerte-titre">
                    <span class="pv-alerte-direct"><i></i>En ce moment</span>
                    <h2>Alerte {{ $alerteEnCours === 'vert' ? 'verte' : $alerteEnCours }} en cours</h2>
                </div>
                <ol class="pv-jauge" aria-label="Niveau d’alerte">
                    @foreach(\App\Models\FicheConseil::NIVEAUX as $valeur => $libelle)
                        <li class="pv-jauge-{{ $valeur }} {{ $loop->index <= $positionAlerte ? 'is-atteint' : '' }} {{ $valeur === $alerteEnCours ? 'is-actuel' : '' }}" @if($valeur === $alerteEnCours) aria-current="true" @endif><span>{{ $libelle }}</span></li>
                    @endforeach
                </ol>
            </div>

            @if($conseilsAlerte->isEmpty())
                <p class="pv-alerte-vide">Aucun conseil n’est encore disponible pour ce niveau.</p>
            @else
                <h3>À lire en priorité</h3>
                <div class="pv-alerte-conseils">
                    @foreach($conseilsAlerte as $conseil)
                        <a class="pv-alerte-conseil" href="{{ route('prevention.fiches.show', $conseil) }}">
                            @if($conseil->image)
                                <img src="{{ $conseil->imageUrl() }}" alt="">
                            @else
                                <span class="pv-alerte-rang">{{ $loop->iteration }}</span>
                            @endif
                            <span class="pv-alerte-texte">
                                <strong>{{ $conseil->titre }}</strong>
                                <small>{{ $conseil->libelleThematique() }}</small>
                            </span>
                            <span class="pv-alerte-fleche" aria-hidden="true">→</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    <section class="ha-admin-panel">
        <form class="ha-user-filters" method="GET" action="{{ route('prevention.fiches.index') }}">
            <label class="ha-search-field"><span class="sr-only">Rechercher</span><x-space-icon name="file" /><input type="search" name="recherche" value="{{ $filtres['recherche'] ?? '' }}" placeholder="Rechercher un conseil"></label>
            <label class="ha-select-field"><span class="sr-only">Thématique</span>
                <select name="thematique">
                    <option value="">Toutes les thématiques</option>
                    @foreach(\App\Models\FicheConseil::THEMATIQUES as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(($filtres['thematique'] ?? '') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </label>
            <button class="ha-secondary-button" type="submit">Rechercher</button>
            @if($filtres)<a class="ha-text-button" href="{{ route('prevention.fiches.index') }}">Effacer</a>@endif
        </form>

        @if($fiches->isEmpty())
            <p class="ha-empty-state">Aucun conseil trouvé.</p>
        @else
            <div class="pv-list">
                @foreach($fiches as $fiche)
                    <article class="pv-item">
                        @if($fiche->image)
                            <img class="pv-item-image" src="{{ $fiche->imageUrl() }}" alt="{{ $fiche->titre }}">
                        @endif
                        <div class="pv-tags">
                            <span class="pv-badge">{{ $fiche->libelleThematique() }}</span>
                            <span class="pv-badge pv-niveau-{{ $fiche->niveau_alerte }}">{{ $fiche->libelleAlerte() }}</span>
                        </div>
                        <h2>{{ $fiche->titre }}</h2>
                        <p>{{ $fiche->resume }}</p>
                        <div class="ha-row-actions"><a href="{{ route('prevention.fiches.show', $fiche) }}">Lire le conseil →</a></div>
                    </article>
                @endforeach
            </div>
        @endif
        @if($fiches->hasPages())<div class="ha-pagination">{{ $fiches->links() }}</div>@endif
    </section>
</div>
@endsection
