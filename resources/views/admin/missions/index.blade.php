@extends('layouts.space', ['space' => 'admin'])

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/heatalert/campagnes.css') }}">
<link rel="stylesheet" href="{{ asset('assets/heatalert/campaign-missions.css') }}">
<link rel="stylesheet" href="{{ asset('assets/heatalert/admin-missions.css') }}">
@endpush

@section('content')
@php
    $campaignStatuses = ['brouillon' => 'Brouillon', 'publiee' => 'Publiée', 'en_cours' => 'En cours', 'terminee' => 'Terminée', 'annulee' => 'Annulée'];
    $missionStatuses = ['ouverte' => 'Ouverte', 'complete' => 'Complète', 'en_cours' => 'En cours', 'terminee' => 'Terminée', 'annulee' => 'Annulée'];
@endphp
<div class="ha-admin-page ha-admin-missions-page">
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Administration / Missions</p>
            <h1>Les missions par campagne</h1>
            <p class="ha-muted">Consultez les missions de toutes les campagnes et leurs informations sur le terrain.</p>
        </div>
        <a class="ha-secondary-button" href="{{ route('admin.campagnes.index') }}">Voir les campagnes</a>
    </header>

    <div class="ha-campaign-summary" aria-label="Résumé des missions">
        <div><span>Campagnes</span><strong>{{ $campagnes->count() }}</strong></div>
        <div><span>Total des missions</span><strong>{{ $totalMissions }}</strong></div>
        <div><span>Missions en cours</span><strong>{{ $missionsEnCours }}</strong></div>
    </div>

    <div class="ha-admin-campaign-groups">
        @forelse($campagnes as $campagne)
        <section class="ha-admin-campaign-group" aria-labelledby="campaign-title-{{ $campagne->id }}" data-campaign-id="{{ $campagne->id }}">
            <header class="ha-admin-campaign-header">
                <div class="ha-admin-campaign-heading">
                    <span class="ha-admin-campaign-icon" aria-hidden="true"><x-space-icon name="flag" /></span>
                    <div>
                        <p class="ha-eyebrow">Campagne #{{ $campagne->id }}</p>
                        <h2 id="campaign-title-{{ $campagne->id }}">{{ $campagne->titre }}</h2>
                    </div>
                </div>
                <span class="ha-campaign-status" data-status="{{ $campagne->statut }}">{{ $campaignStatuses[$campagne->statut] ?? $campagne->statut }}</span>
                <p class="ha-admin-campaign-description">{{ $campagne->description }}</p>
                <div class="ha-admin-campaign-meta">
                    <span><x-space-icon name="pin" />{{ $campagne->lieu }}</span>
                    <span><x-space-icon name="user" />{{ $campagne->organisateur?->name ?? 'Agent indisponible' }}</span>
                    <span>Du {{ $campagne->date_debut->format('d/m/Y') }} au {{ $campagne->date_fin->format('d/m/Y') }}</span>
                </div>
            </header>
            <div class="ha-admin-campaign-body">
                <h3 class="ha-admin-missions-count">{{ $campagne->missions->count() }} {{ $campagne->missions->count() === 1 ? 'mission liée' : 'missions liées' }}</h3>
                <div class="ha-admin-mission-grid">
                    @forelse($campagne->missions as $mission)
                    <article class="ha-mission-card ha-admin-mission-card" aria-labelledby="mission-title-{{ $mission->id }}" data-mission-id="{{ $mission->id }}">
                        <header>
                            <h4 id="mission-title-{{ $mission->id }}">{{ $mission->titre }}</h4>
                            <span class="ha-mission-badge" data-status="{{ $mission->statut }}">{{ $missionStatuses[$mission->statut] ?? $mission->statut }}</span>
                        </header>
                        <p class="ha-mission-description">{{ $mission->description }}</p>
                        <dl class="ha-mission-meta">
                            <div><dt>Lieu</dt><dd>{{ $mission->lieu }}</dd></div>
                            <div><dt>Nombre de places</dt><dd>{{ $mission->nombre_places }} {{ $mission->nombre_places === 1 ? 'place' : 'places' }}</dd></div>
                            <div><dt>Début</dt><dd><time datetime="{{ $mission->date_debut->toIso8601String() }}">{{ $mission->date_debut->format('d/m/Y à H:i') }}</time></dd></div>
                            <div><dt>Fin</dt><dd><time datetime="{{ $mission->date_fin->toIso8601String() }}">{{ $mission->date_fin->format('d/m/Y à H:i') }}</time></dd></div>
                        </dl>
                    </article>
                    @empty
                    <p class="ha-admin-missions-empty">Aucune mission liée à cette campagne pour le moment.</p>
                    @endforelse
                </div>
            </div>
        </section>
        @empty
        <section class="ha-admin-panel ha-admin-missions-empty">
            <x-space-icon name="flag" />
            <h2>Aucune campagne pour le moment</h2>
            <p>Les campagnes et leurs missions apparaîtront ici dès leur création.</p>
            <a class="ha-primary-button" href="{{ route('admin.campagnes.index') }}">Gérer les campagnes</a>
        </section>
        @endforelse
    </div>
</div>
@endsection
