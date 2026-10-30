@extends('layouts.space', ['space' => $space])

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<link rel="stylesheet" href="{{ asset('assets/heatalert/signalements.css') }}">
@endpush
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="{{ asset('assets/heatalert/signalements.js') }}" defer></script>
@endpush

@section('content')
<div class="ha-admin-page ha-signalements-page" data-signalements-map data-api-url="{{ route('user.signalements.api.ressources') }}">
    <header class="ha-admin-heading">
        <div><p class="ha-eyebrow">Signalements et ressources</p><h1>Ressources utiles en Tunisie</h1><p class="ha-muted">Consultez les points d’eau, espaces de fraîcheur et centres d’accueil signalés par la communauté.</p></div>
        <a class="ha-primary-button" href="{{ route('user.signalements.create') }}">+ Signaler</a>
    </header>

    <section class="ha-map-toolbar" aria-label="Filtres de la carte">
        <label class="ha-map-search"><span>Rechercher</span><input type="search" placeholder="Nom, adresse ou gouvernorat" data-map-filter="q"></label>
        <label><span>Gouvernorat</span><select data-map-filter="gouvernorat"><option value="">Tous</option><option>Tunis</option><option>Sfax</option><option>Sousse</option><option>Kairouan</option><option>Gabès</option><option>Tozeur</option><option>Bizerte</option><option>Gafsa</option><option>Nabeul</option><option>Médenine</option></select></label>
        <label><span>Type</span><select data-map-filter="type"><option value="">Tous les types</option><option value="point_eau">Point d’eau</option><option value="espace_fraicheur">Espace de fraîcheur</option><option value="centre_accueil">Centre d’accueil</option></select></label>
        <label><span>État</span><select data-map-filter="etat"><option value="">Tous les états</option><option value="rouge">Incident actif</option><option value="vert">Ressource OK</option></select></label>
    </section>
    <div class="ha-map-stats"><div><strong data-map-count="incidents_actifs">0</strong><span>incidents actifs</span></div><div><strong data-map-count="ressources_ok">0</strong><span>ressources OK</span></div></div>
    <div class="ha-map-shell"><div id="signalements-map" class="ha-leaflet-map" aria-label="Carte des ressources utiles"></div><p class="ha-map-status" data-map-status role="status"></p></div>
    <p class="ha-map-more"><a class="ha-secondary-button" href="{{ route('user.signalements.public.index') }}">Voir les signalements validés des citoyens</a></p>
</div>
@endsection
