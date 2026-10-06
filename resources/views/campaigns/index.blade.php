@extends('layouts.space', ['space' => 'user'])

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/heatalert/campagnes.css') }}">
<link rel="stylesheet" href="{{ asset('assets/heatalert/campaign-missions.css') }}">
@endpush
@push('scripts')
<script src="{{ asset('assets/heatalert/campaign-missions.js') }}" defer></script>
@endpush

@section('content')
@php
    $statuses = ['publiee' => 'Publiées', 'en_cours' => 'En cours', 'brouillon' => 'Brouillons', 'terminee' => 'Terminées', 'annulee' => 'Annulées'];
    $labels = ['publiee' => 'Publiée', 'en_cours' => 'En cours', 'brouillon' => 'Brouillon', 'terminee' => 'Terminée', 'annulee' => 'Annulée'];
    $tones = ['publiee' => 'water', 'en_cours' => 'sun', 'brouillon' => 'care', 'terminee' => 'care', 'annulee' => 'care'];
@endphp
<div class="ha-admin-page ha-missions-page" data-missions-page data-user-id="{{ auth()->id() }}" data-campaign-url="{{ route('user.campagnes') }}" data-participation-url="{{ url('/user/missions') }}" data-csrf="{{ csrf_token() }}">
    <header class="ha-admin-heading">
        <div><p class="ha-eyebrow">Campagnes et bénévolat</p><h1>Agir ensemble contre la chaleur</h1><p class="ha-muted">Découvrez toutes les campagnes par état. Les campagnes proposant des missions ouvertes apparaissent en priorité.</p></div>
        <div class="ha-heading-stat"><strong>{{ $campagnes->count() }}</strong><span>campagnes à découvrir</span></div>
    </header>
    <div class="ha-missions-search"><label class="ha-search-field"><x-space-icon name="flag" /><span class="sr-only">Rechercher une campagne</span><input type="search" placeholder="Rechercher une campagne ou un lieu…" data-campaign-search></label></div>
    <p class="ha-notice ha-notice-error" data-campaign-error role="alert" tabindex="-1" hidden></p>
    @foreach($campaignGroups as $priority => $groups)
    @if($groups->isNotEmpty())
    <section class="ha-user-campaign-priority" data-priority-group aria-labelledby="campaign-priority-{{ $loop->index }}">
        <h2 class="ha-user-priority-title" id="campaign-priority-{{ $loop->index }}">{{ $priority }}</h2>
        @foreach($statuses as $status => $label)
        @if(isset($groups[$status]))
        <section data-status-group aria-label="Campagnes {{ mb_strtolower($label) }}">
            <h3 class="ha-user-status-title">{{ $label }}</h3>
            <div class="ha-campaign-cards">
                @foreach($groups[$status] as $campagne)
                <article class="ha-discovery-card" data-campaign-id="{{ $campagne->id }}" data-search="{{ $campagne->titre }} {{ $campagne->lieu }}">
                    <div class="ha-card-art" data-tone="{{ $tones[$status] }}"><strong>{{ $campagne->lieu }}</strong><span>Prévention &amp; solidarité</span></div>
                    <div class="ha-card-body">
                        <span class="ha-campaign-status" data-status="{{ $status }}">{{ $labels[$status] }}</span>
                        <h4>{{ $campagne->titre }}</h4><p>{{ $campagne->description }}</p>
                        <div class="ha-card-meta"><span>{{ $campagne->date_debut->format('d/m/Y') }} → {{ $campagne->date_fin->format('d/m/Y') }}</span><span>{{ $campagne->missions->count() }} mission(s) à découvrir</span></div>
                        <button class="ha-primary-button" type="button" data-open-campaign="{{ $campagne->id }}" aria-haspopup="dialog" aria-label="Découvrir les missions : {{ $campagne->titre }}">Découvrir les missions</button>
                    </div>
                    <script type="application/json" data-campaign-info>{!! \Illuminate\Support\Js::encode($campagne->only(['id', 'titre', 'description', 'objectif', 'lieu']) + ['date_debut' => $campagne->date_debut->format('Y-m-d'), 'date_fin' => $campagne->date_fin->format('Y-m-d')]) !!}</script>
                </article>
                @endforeach
            </div>
        </section>
        @endif
        @endforeach
    </section>
    @endif
    @endforeach
    <p class="ha-empty-state" data-campaigns-empty @if($campagnes->isNotEmpty()) hidden @endif>{{ $campagnes->isEmpty() ? 'Aucune campagne pour le moment.' : 'Aucune campagne ne correspond à votre recherche.' }}</p>

    <dialog class="ha-campaign-dialog ha-missions-dialog" data-missions-dialog aria-labelledby="missions-title" aria-describedby="missions-description">
        <header class="ha-dialog-heading"><div><p class="ha-eyebrow">Campagne / Missions</p><h2 id="missions-title" data-selected-campaign></h2></div><button class="ha-icon-button" type="button" data-close-dialog aria-label="Fermer les missions"><x-space-icon name="close" /></button></header>
        <p class="ha-muted" id="missions-description" data-selected-description></p>
        <div class="ha-campaign-context" data-campaign-context></div>
        <div class="ha-missions-toolbar"><h3>Missions de la campagne</h3></div>
        <p class="ha-notice" role="status" data-mission-notice tabindex="-1" hidden></p>
        <div class="ha-mission-list" data-mission-list></div>
        <footer class="ha-dialog-actions"><button class="ha-secondary-button" type="button" data-close-dialog>Fermer</button></footer>
    </dialog>
</div>
@endsection
