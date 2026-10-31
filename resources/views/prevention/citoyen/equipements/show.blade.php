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

    @include('prevention.citoyen.equipements._conseils_ia')

    <div class="ha-detail-grid">
        <section class="ha-admin-panel">
            <h2>Description du problème</h2>
            <p class="pv-info">{!! $equipement->description ? nl2br(e($equipement->description)) : 'Aucune description.' !!}</p>
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
