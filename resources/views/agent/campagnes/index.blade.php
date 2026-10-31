@extends('layouts.space', ['space' => 'agent-campagne'])

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/heatalert/campagnes.css') }}">
<link rel="stylesheet" href="{{ asset('assets/heatalert/campaign-missions.css') }}">
<link rel="stylesheet" href="{{ asset('assets/heatalert/admin-missions.css') }}">
<link rel="stylesheet" href="{{ asset('assets/heatalert/agent-missions.css') }}">
@endpush
@push('scripts')
<script src="{{ asset('assets/heatalert/agent-missions.js') }}" defer></script>
@endpush

@section('content')
@php
    $campaignStatuses = ['publiee' => 'Publiées', 'en_cours' => 'En cours', 'brouillon' => 'Brouillons', 'terminee' => 'Terminées', 'annulee' => 'Annulées'];
    $campaignLabels = ['brouillon' => 'Brouillon', 'publiee' => 'Publiée', 'en_cours' => 'En cours', 'terminee' => 'Terminée', 'annulee' => 'Annulée'];
    $missionStatuses = ['ouverte' => 'Ouvertes', 'en_cours' => 'En cours', 'complete' => 'Complètes', 'terminee' => 'Terminées', 'annulee' => 'Annulées'];
    $missionLabels = ['ouverte' => 'Ouverte', 'complete' => 'Complète', 'en_cours' => 'En cours', 'terminee' => 'Terminée', 'annulee' => 'Annulée'];
@endphp
<div class="ha-admin-page" data-agent-missions-page data-user-id="{{ auth()->id() }}" data-endpoint="{{ route('agent-campagne.missions') }}" data-csrf="{{ csrf_token() }}">
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Espace campagnes / {{ $mode === 'campagnes' ? 'Mes campagnes' : 'Mes missions' }}</p>
            <h1>{{ $mode === 'campagnes' ? 'Mes campagnes affectées' : 'Les missions de mes campagnes' }}</h1>
            <p class="ha-muted">Vos campagnes à venir en priorité, puis les autres campagnes regroupées par état.</p>
        </div>
        <button class="ha-primary-button" type="button" data-mission-create @disabled($campagnes->isEmpty()) aria-haspopup="dialog">Ajouter une mission</button>
    </header>
    @if(session('success'))<p class="ha-notice ha-notice-success" role="status">{{ session('success') }}</p>@endif
    <p class="ha-notice ha-notice-error" role="alert" data-page-error tabindex="-1" hidden></p>
    <div class="ha-campaign-summary">
        <div><span>Campagnes affectées</span><strong>{{ $campagnes->count() }}</strong></div>
        <div><span>Campagnes à venir</span><strong>{{ $campaignGroups['À venir']->flatten(1)->count() }}</strong></div>
        <div><span>Mes missions</span><strong>{{ $totalMissions }}</strong></div>
    </div>

    @forelse($campaignGroups->filter(fn ($groups) => $groups->isNotEmpty()) as $period => $groups)
    <section class="ha-agent-period" aria-labelledby="period-{{ $loop->index }}">
        <h2 class="ha-agent-period-title" id="period-{{ $loop->index }}">{{ $period }}</h2>
        @foreach($campaignStatuses as $status => $statusLabel)
        @if(isset($groups[$status]))
        <section class="ha-agent-status-group" aria-label="Campagnes {{ mb_strtolower($statusLabel) }}">
            <h3 class="ha-agent-status-title">{{ $statusLabel }} <span>{{ $groups[$status]->count() }}</span></h3>
            <div class="ha-admin-campaign-groups">
                @foreach($groups[$status] as $campagne)
                <section class="ha-admin-campaign-group" id="campagne-{{ $campagne->id }}" data-campaign-id="{{ $campagne->id }}" aria-labelledby="campaign-title-{{ $campagne->id }}">
                    <header class="ha-admin-campaign-header">
                        <div class="ha-admin-campaign-heading"><span class="ha-admin-campaign-icon"><x-space-icon name="flag" /></span><div><p class="ha-eyebrow">Campagne #{{ $campagne->id }}</p><h4 id="campaign-title-{{ $campagne->id }}">{{ $campagne->titre }}</h4></div></div>
                        <span class="ha-campaign-status" data-status="{{ $campagne->statut }}">{{ $campaignLabels[$campagne->statut] }}</span>
                        <p class="ha-admin-campaign-description">{{ $campagne->description }}</p>
                        <div class="ha-admin-campaign-meta"><span><x-space-icon name="pin" />{{ $campagne->lieu }}</span><span>Du {{ $campagne->date_debut->format('d/m/Y') }} au {{ $campagne->date_fin->format('d/m/Y') }}</span><span>{{ $campagne->missions->count() }} mission(s)</span></div>
                    </header>
                    <div class="ha-admin-campaign-body">
                        <p><button type="button" class="ha-secondary-button" data-campaign-report="{{ route('agent-campagne.campagnes.rapport', $campagne) }}" data-campaign-id="{{ $campagne->id }}">Rapport IA · Gemini / PDF</button></p>
                        @if($mode === 'campagnes')
                        <p class="ha-agent-objective"><strong>Objectif :</strong> {{ $campagne->objectif }}</p>
                        <div class="ha-mission-actions"><a class="ha-secondary-button" href="{{ route('agent-campagne.missions') }}#campagne-{{ $campagne->id }}">Consulter les missions</a><button class="ha-primary-button" type="button" data-mission-create="{{ $campagne->id }}" aria-haspopup="dialog">Ajouter une mission</button></div>
                        @else
                        <div class="ha-missions-toolbar"><h5>Missions de la campagne</h5><button class="ha-primary-button" type="button" data-mission-create="{{ $campagne->id }}" aria-haspopup="dialog">Ajouter une mission</button></div>
                        @foreach($missionStatuses as $missionStatus => $missionStatusLabel)
                        @php $missions = $campagne->missions->where('statut', $missionStatus); @endphp
                        @if($missions->isNotEmpty())
                        <section class="ha-agent-mission-state" aria-label="Missions {{ mb_strtolower($missionStatusLabel) }}">
                            <h6>{{ $missionStatusLabel }} · {{ $missions->count() }}</h6>
                            <div class="ha-admin-mission-grid">
                            @foreach($missions as $mission)
                                <article class="ha-mission-card ha-admin-mission-card" data-mission-id="{{ $mission->id }}" aria-labelledby="mission-title-{{ $mission->id }}">
                                    <header><h6 id="mission-title-{{ $mission->id }}">{{ $mission->titre }}</h6><span class="ha-mission-badge" data-status="{{ $mission->statut }}">{{ $missionLabels[$mission->statut] }}</span></header>
                                    <p class="ha-mission-description">{{ $mission->description }}</p>
                                    <dl class="ha-mission-meta"><div><dt>Lieu</dt><dd>{{ $mission->lieu }}</dd></div><div><dt>Places</dt><dd>{{ $mission->nombre_places }}</dd></div><div><dt>Début</dt><dd>{{ $mission->date_debut->format('d/m/Y à H:i') }}</dd></div><div><dt>Fin</dt><dd>{{ $mission->date_fin->format('d/m/Y à H:i') }}</dd></div></dl>
                                    <div class="ha-mission-actions"><button class="ha-secondary-button" type="button" data-mission-details="{{ $mission->id }}">Détails / Participants</button><button class="ha-secondary-button" type="button" data-mission-edit="{{ $mission->id }}">Modifier</button><button class="ha-danger-button" type="button" data-mission-delete="{{ $mission->id }}" data-mission-title="{{ $mission->titre }}">Supprimer</button></div>
                                </article>
                            @endforeach
                            </div>
                        </section>
                        @endif
                        @endforeach
                        @if($campagne->missions->isEmpty())<p class="ha-admin-missions-empty">Aucune mission pour cette campagne. Ajoutez votre première mission.</p>@endif
                        @endif
                    </div>
                </section>
                @endforeach
            </div>
        </section>
        @endif
        @endforeach
    </section>
    @empty
    <section class="ha-admin-panel ha-admin-missions-empty"><h2>Aucune campagne affectée</h2><p>Vos campagnes apparaîtront ici lorsqu’un administrateur vous les affectera.</p></section>
    @endforelse

    <dialog class="ha-campaign-dialog" data-editor-dialog aria-labelledby="mission-editor-title">
        <header class="ha-dialog-heading"><h2 id="mission-editor-title">Ajouter une mission</h2><button type="button" class="ha-icon-button" data-dialog-close aria-label="Fermer"><x-space-icon name="close" /></button></header>
        <form class="ha-campaign-form" data-mission-form method="POST" action="{{ route('agent-campagne.missions.store') }}">
            @csrf
            <p class="ha-notice ha-notice-error" data-form-error tabindex="-1" role="alert" hidden></p>
            <p class="ha-muted" data-campaign-context hidden></p>
            <div class="ha-form-grid">
                <label class="ha-field-wide" data-campaign-field>Campagne<select name="campagne_id" required><option value="">Choisir une campagne affectée</option>@foreach($campagnes as $campagne)<option value="{{ $campagne->id }}" data-start="{{ $campagne->date_debut->format('Y-m-d') }}" data-end="{{ $campagne->date_fin->format('Y-m-d') }}">{{ $campagne->titre }} ({{ $campaignLabels[$campagne->statut] }})</option>@endforeach</select></label>
                <label class="ha-field-wide">Titre<input name="titre" maxlength="255" required></label>
                <label class="ha-field-wide">Description<textarea name="description" rows="3" required></textarea></label>
                <label class="ha-field-wide">Lieu<input name="lieu" maxlength="255" required></label>
                <label>Date et heure de début<input name="date_debut" type="datetime-local" required></label>
                <label>Date et heure de fin<input name="date_fin" type="datetime-local" required></label>
                <label>Nombre de places<input name="nombre_places" type="number" min="1" max="100000" required></label>
                <label>Statut<select name="statut" required>@foreach($missionLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
            </div>
            <small class="ha-muted">Les dates de la mission doivent rester dans la période de la campagne. Horaires locaux.</small>
            <footer class="ha-dialog-actions"><button class="ha-secondary-button" type="button" data-dialog-close>Annuler</button><button class="ha-primary-button" type="submit">Enregistrer la mission</button></footer>
        </form>
    </dialog>
    <dialog class="ha-campaign-dialog ha-participants-dialog" data-participants-dialog aria-labelledby="mission-details-title">
        <header class="ha-dialog-heading"><h2 id="mission-details-title"></h2><button class="ha-icon-button" type="button" data-dialog-close aria-label="Fermer"><x-space-icon name="close" /></button></header>
        <dl class="ha-mission-meta" data-mission-details-content></dl>
        <h3 class="ha-agent-status-title">Participants</h3>
        <div class="ha-table-wrap"><table class="ha-users-table ha-participants-table"><thead><tr><th>Participant</th><th>Motivation</th><th>Inscription</th><th>Présence</th></tr></thead><tbody data-participants-list></tbody></table></div>
        <footer class="ha-dialog-actions"><button class="ha-secondary-button" type="button" data-dialog-close>Fermer</button></footer>
    </dialog>
    <dialog class="ha-campaign-dialog ha-confirm-dialog" data-delete-dialog aria-labelledby="delete-mission-title">
        <h2 id="delete-mission-title">Supprimer cette mission ?</h2>
        <p>La mission « <strong data-delete-title></strong> » et ses participations seront définitivement supprimées.</p>
        <p class="ha-notice ha-notice-error" data-delete-error tabindex="-1" role="alert" hidden></p>
        <footer class="ha-dialog-actions"><button class="ha-secondary-button" type="button" data-dialog-close>Annuler</button><button class="ha-danger-button" type="button" data-confirm-delete>Supprimer la mission</button></footer>
    </dialog>
</div>
@endsection
