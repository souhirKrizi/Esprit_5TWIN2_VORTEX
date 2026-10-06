@extends('layouts.space', ['space' => 'user'])

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/heatalert/campagnes.css') }}">
<link rel="stylesheet" href="{{ asset('assets/heatalert/campaign-missions.css') }}">
@endpush

@section('content')
@php
    $missionLabels = ['ouverte' => 'Ouverte', 'complete' => 'Complète', 'en_cours' => 'En cours', 'terminee' => 'Terminée', 'annulee' => 'Annulée'];
    $participationLabels = ['acceptee' => 'Acceptée', 'en_attente' => 'En attente', 'refusee' => 'Refusée', 'annulee' => 'Annulée'];
@endphp
<div class="ha-admin-page">
    <header class="ha-admin-heading">
        <div><p class="ha-eyebrow">Campagnes et bénévolat / Mes participations</p><h1>Mes participations</h1><p class="ha-muted">Retrouvez les missions auxquelles vous êtes inscrit et les informations de votre participation.</p></div>
        <a class="ha-primary-button" href="{{ route('user.campagnes') }}">Découvrir les campagnes</a>
    </header>
    <div class="ha-campaign-summary ha-participation-summary">
        <div><span>Mes inscriptions</span><strong>{{ $participations->count() }}</strong></div>
        <div><span>Participations acceptées</span><strong>{{ $participations->where('statut', 'acceptee')->count() }}</strong></div>
        <div><span>Missions en cours</span><strong>{{ $participations->filter(fn ($participation) => $participation->statut === 'acceptee' && $participation->mission->statut === 'en_cours')->count() }}</strong></div>
    </div>
    <div class="ha-campaign-cards ha-participation-cards">
        @forelse($participations as $participation)
        @php $mission = $participation->mission; @endphp
        <article class="ha-discovery-card" data-participation-id="{{ $participation->id }}" data-mission-id="{{ $mission->id }}" aria-labelledby="participation-title-{{ $participation->id }}">
            <div class="ha-card-art" data-tone="water"><strong class="ha-participation-campaign">{{ $mission->campagne->titre }}</strong><span>{{ $mission->campagne->lieu }}</span></div>
            <div class="ha-card-body">
                <span class="ha-mission-badge" data-status="{{ $participation->statut }}">Participation {{ mb_strtolower($participationLabels[$participation->statut] ?? $participation->statut) }}</span>
                <h2 id="participation-title-{{ $participation->id }}">{{ $mission->titre }}</h2>
                <p class="ha-mission-description">{{ $mission->description }}</p>
                <dl class="ha-mission-meta">
                    <div><dt>Lieu de la mission</dt><dd>{{ $mission->lieu }}</dd></div>
                    <div><dt>État de la mission</dt><dd><span class="ha-mission-badge" data-status="{{ $mission->statut }}">{{ $missionLabels[$mission->statut] ?? $mission->statut }}</span></dd></div>
                    <div><dt>Début</dt><dd>{{ $mission->date_debut->format('d/m/Y à H:i') }}</dd></div>
                    <div><dt>Fin</dt><dd>{{ $mission->date_fin->format('d/m/Y à H:i') }}</dd></div>
                    <div><dt>Nombre de places</dt><dd>{{ $mission->nombre_places }}</dd></div>
                    <div><dt>Date d’inscription</dt><dd>{{ $participation->created_at->format('d/m/Y') }}</dd></div>
                </dl>
            </div>
        </article>
        @empty
        <section class="ha-admin-panel ha-participation-empty"><x-space-icon name="check" /><h2>Aucune participation pour le moment</h2><p>Découvrez les campagnes, ouvrez leurs missions et cliquez sur « Participer » pour vous inscrire.</p><a class="ha-primary-button" href="{{ route('user.campagnes') }}">Trouver une mission</a></section>
        @endforelse
    </div>
</div>
@endsection
