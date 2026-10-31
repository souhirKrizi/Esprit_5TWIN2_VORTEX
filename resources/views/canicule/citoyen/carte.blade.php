@extends('layouts.space', ['space' => 'user'])

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
@include('prevention.partials.assets')
<div class="ha-admin-page ha-canicule-page" data-canicule-map>
    <header class="ha-admin-heading">
        <div><p class="ha-eyebrow">Météo et canicule</p><h1>Carte des alertes</h1><p class="ha-muted">Consultez le niveau de vigilance dans chaque gouvernorat.</p></div>
        <nav class="ha-canicule-actions" aria-label="Pages météo">
            <a class="ha-secondary-button" href="{{ route('user.canicule.meteo') }}">Météo de ma zone</a>
            <a class="ha-secondary-button" href="{{ route('user.canicule.alertes') }}">Alertes en cours</a>
        </nav>
    </header>
    <section class="ha-admin-panel ha-canicule-map-panel">
        <div class="ha-canicule-legend" aria-label="Niveaux de gravité">
            <span><i class="ha-canicule-swatch is-jaune"></i> Vigilance</span>
            <span><i class="ha-canicule-swatch is-orange"></i> Alerte</span>
            <span><i class="ha-canicule-swatch is-rouge"></i> Alerte extrême</span>
            <span><i class="ha-canicule-swatch is-neutre"></i> Sans alerte active</span>
        </div>
        <div class="ha-map-shell"><div id="canicule-map" class="ha-leaflet-map" aria-label="Carte des gouvernorats et alertes canicule"></div></div>
        <p class="ha-canicule-attribution">Limites : geoBoundaries, OpenStreetMap contributors, licence ODbL 1.0. Fonds de carte : © OpenStreetMap.</p>
        <script type="application/json" data-canicule-geojson>{!! $geojson !!}</script>
    </section>
</div>
@endsection