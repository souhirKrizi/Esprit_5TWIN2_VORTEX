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
        <section class="pv-alerte pv-alerte-{{ $alerteEnCours }}">
            <h2>Alerte {{ strtolower(\App\Models\FicheConseil::NIVEAUX[$alerteEnCours]) }} en cours</h2>
            @if($conseilsAlerte->isEmpty())
                <p>Aucun conseil n’est encore disponible pour ce niveau.</p>
            @else
                <p>À lire en priorité :</p>
                <ul>
                    @foreach($conseilsAlerte as $conseil)
                        <li><a href="{{ route('prevention.fiches.show', $conseil) }}">{{ $conseil->titre }}</a></li>
                    @endforeach
                </ul>
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
                            <span class="pv-badge pv-niveau-{{ $fiche->niveau_alerte }}">Alerte {{ strtolower($fiche->libelleNiveau()) }}</span>
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
