@extends('layouts.space', ['space' => 'admin'])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page ha-canicule-admin">
    <a class="ha-back-link" href="{{ route('admin.canicule.zones.index') }}">Retour aux gouvernorats</a>
    <header class="ha-admin-heading"><div><p class="ha-eyebrow">Administration / Météo et canicule</p><h1>Gérer le gouvernorat</h1><p class="ha-muted">Les limites géographiques sont protégées.</p></div></header>
    @include('admin.canicule.partials.messages')
    <section class="ha-admin-panel pv-box"><form class="pv-form" method="POST" action="{{ route('admin.canicule.zones.update', $zone) }}">
        @csrf @method('PUT')
        <div class="pv-grid-2">
            <div class="pv-field"><label for="zone-nom">Gouvernorat</label><input id="zone-nom" class="ha-canicule-readonly" value="{{ $zone->nom }}" readonly></div>
            <div class="pv-field"><label for="zone-code">Code</label><input id="zone-code" class="ha-canicule-readonly" value="{{ $zone->code }}" readonly></div>
            <div class="pv-field pv-field-wide"><div class="ha-canicule-lock"><strong>Frontière GeoJSON protégée</strong><span>La géométrie utilisée pour tracer ce gouvernorat sur la carte est conservée telle quelle.</span></div></div>
            <div class="pv-field"><label for="active">Disponibilité sur la carte</label><select id="active" name="active"><option value="1" @selected((string) old('active', (int) $zone->active) === '1')>Afficher ce gouvernorat</option><option value="0" @selected((string) old('active', (int) $zone->active) === '0')>Masquer temporairement</option></select></div>
        </div>
        <div class="pv-actions"><a class="ha-secondary-button" href="{{ route('admin.canicule.zones.index') }}">Annuler</a><button class="ha-primary-button" type="submit">Enregistrer</button></div>
    </form></section>
</div>
@endsection