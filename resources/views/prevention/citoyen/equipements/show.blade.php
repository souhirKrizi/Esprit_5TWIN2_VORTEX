@extends('layouts.space', ['space' => $espace])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page">
    <a class="ha-back-link" href="{{ route('prevention.equipements.index') }}">← Retour à mes équipements</a>
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Mes équipements</p>
            <h1>{{ $equipement->nom }}</h1>
        </div>
        <div class="pv-heading-actions">
            <a class="ha-secondary-button" href="{{ route('prevention.equipements.edit', $equipement) }}">Modifier</a>
            <form class="pv-inline-form" method="POST" action="{{ route('prevention.equipements.destroy', $equipement) }}" data-confirm="Supprimer l’équipement « {{ $equipement->nom }} » ?">
                @csrf @method('DELETE')
                <button class="ha-danger-button" type="submit">Supprimer</button>
            </form>
        </div>
    </header>

    @include('prevention.partials.messages')

    <div class="ha-detail-grid">
        <section class="ha-admin-panel">
            <h2>Conseils pour cet équipement</h2>
            @forelse($conseils as $fiche)
                <div class="pv-qr">
                    <a href="{{ route('prevention.fiches.show', $fiche) }}"><strong>{{ $fiche->titre }}</strong></a>
                    <p>{{ $fiche->resume }}</p>
                </div>
            @empty
                <p class="pv-info">Aucun conseil n’est encore disponible pour ce type d’équipement.</p>
            @endforelse
        </section>

        <section class="ha-admin-panel">
            <h2>Informations</h2>
            <dl class="ha-details-list">
                <div><dt>Type</dt><dd>{{ $equipement->libelleType() }}</dd></div>
                <div><dt>Sensible à</dt><dd>{{ $equipement->libelleSensibilite() }}</dd></div>
                <div><dt>Autonomie sans courant</dt><dd>{{ $equipement->autonomie_heures === null ? 'Non renseignée' : $equipement->autonomie_heures.' h' }}</dd></div>
                <div><dt>Ajouté le</dt><dd>{{ $equipement->created_at->format('d/m/Y') }}</dd></div>
            </dl>
        </section>
    </div>
</div>
@endsection
