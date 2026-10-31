@extends('layouts.space', ['space' => 'admin'])

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
<link rel="stylesheet" href="{{ asset('assets/heatalert/signalements.css') }}">
<link rel="stylesheet" href="{{ asset('assets/heatalert/canicule.css') }}">
@endpush
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
<script src="{{ asset('assets/heatalert/canicule.js') }}" defer></script>
@endpush

@section('content')
<div class="ha-admin-page ha-canicule-page" data-canicule-map>
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Administration / Météo et canicule</p>
            <h1>Carte des gouvernorats</h1>
            <p class="ha-muted">Les zones colorées ont une alerte manuelle active pendant la période affichée.</p>
        </div>
        <nav class="ha-canicule-actions" aria-label="Gestion météo et canicule">
            <a class="ha-secondary-button" href="{{ route('admin.canicule.releves.index') }}">Relevés météo</a>
            <a class="ha-secondary-button" href="{{ route('admin.canicule.zones.index') }}">Gouvernorats</a>
            <a class="ha-primary-button" href="{{ route('admin.canicule.alertes.create') }}">Créer une alerte</a>
        </nav>
    </header>

    <section class="ha-admin-panel ha-canicule-map-panel">
        <div class="ha-canicule-legend" aria-label="Niveaux de gravité">
            <span><i class="ha-canicule-swatch is-jaune"></i> Vigilance</span>
            <span><i class="ha-canicule-swatch is-orange"></i> Alerte</span>
            <span><i class="ha-canicule-swatch is-rouge"></i> Alerte extrême</span>
            <span><i class="ha-canicule-swatch is-neutre"></i> Sans alerte active</span>
        </div>
        <div class="ha-map-shell"><div id="canicule-map" class="ha-leaflet-map" aria-label="Carte des 24 gouvernorats tunisiens colorés selon les alertes actives"></div></div>
        <p class="ha-canicule-attribution">Limites administratives : geoBoundaries, OpenStreetMap contributors, licence ODbL 1.0. Fonds de carte : © OpenStreetMap.</p>
        <script type="application/json" data-canicule-geojson>{!! $geojson !!}</script>
    </section>
</div>
@endsection