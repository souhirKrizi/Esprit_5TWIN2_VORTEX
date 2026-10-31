@extends('layouts.space', ['space' => 'admin'])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page ha-canicule-admin">
    <a class="ha-back-link" href="{{ route('admin.canicule.releves.index') }}">Retour aux relevés</a>
    <header class="ha-admin-heading"><div><p class="ha-eyebrow">Administration / Météo et canicule</p><h1>{{ $releve->exists ? 'Modifier le relevé' : 'Ajouter un relevé' }}</h1></div></header>
    @include('admin.canicule.partials.messages')
    <section class="ha-admin-panel pv-box"><form class="pv-form" method="POST" action="{{ $releve->exists ? route('admin.canicule.releves.update', $releve) : route('admin.canicule.releves.store') }}">
        @csrf @if($releve->exists) @method('PUT') @endif
        <div class="pv-grid-2">
            <div class="pv-field"><label for="zone_id">Gouvernorat *</label><select id="zone_id" name="zone_id" required>@foreach($zones as $zone)<option value="{{ $zone->id }}" @selected((string) old('zone_id', $releve->zone_id) === (string) $zone->id)>{{ $zone->nom }}</option>@endforeach</select>@error('zone_id')<p class="pv-erreur">{{ $message }}</p>@enderror</div>
            <div class="pv-field"><label for="type">Type *</label><select id="type" name="type">@foreach(\App\Models\ReleveMeteo::TYPES as $value => $label)<option value="{{ $value }}" @selected(old('type', $releve->type) === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="pv-field"><label for="date_mesure">Date et heure *</label><input id="date_mesure" type="datetime-local" name="date_mesure" value="{{ old('date_mesure', $releve->date_mesure?->format('Y-m-d\TH:i')) }}" required>@error('date_mesure')<p class="pv-erreur">{{ $message }}</p>@enderror</div>
            <div class="pv-field"><label for="temperature">Température (°C) *</label><input id="temperature" type="number" step="0.1" min="-30" max="60" name="temperature" value="{{ old('temperature', $releve->temperature) }}" required>@error('temperature')<p class="pv-erreur">{{ $message }}</p>@enderror</div>
            <div class="pv-field"><label for="humidite">Humidité (%) *</label><input id="humidite" type="number" min="0" max="100" name="humidite" value="{{ old('humidite', $releve->humidite) }}" required>@error('humidite')<p class="pv-erreur">{{ $message }}</p>@enderror</div>
            <div class="pv-field"><label for="vitesse_vent">Vent (km/h)</label><input id="vitesse_vent" type="number" step="0.1" min="0" max="300" name="vitesse_vent" value="{{ old('vitesse_vent', $releve->vitesse_vent) }}">@error('vitesse_vent')<p class="pv-erreur">{{ $message }}</p>@enderror</div>
            <div class="pv-field"><label for="indice_uv">Indice UV</label><input id="indice_uv" type="number" step="0.1" min="0" max="30" name="indice_uv" value="{{ old('indice_uv', $releve->indice_uv) }}">@error('indice_uv')<p class="pv-erreur">{{ $message }}</p>@enderror</div>
            <div class="pv-field"><label for="source">Source *</label><input id="source" name="source" maxlength="100" value="{{ old('source', $releve->source ?? 'Saisie manuelle') }}" required>@error('source')<p class="pv-erreur">{{ $message }}</p>@enderror</div>
        </div>
        <div class="pv-actions"><a class="ha-secondary-button" href="{{ route('admin.canicule.releves.index') }}">Annuler</a><button class="ha-primary-button" type="submit">Enregistrer</button></div>
    </form></section>
</div>
@endsection