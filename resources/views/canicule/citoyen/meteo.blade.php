@extends('layouts.space', ['space' => 'user'])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page ha-canicule-admin">
    <header class="ha-admin-heading">
        <div><p class="ha-eyebrow">Météo et canicule</p><h1>Météo de ma zone</h1><p class="ha-muted">{{ $zone ? $zone->nom : auth()->user()->governorate }}</p></div>
        <nav class="ha-canicule-actions" aria-label="Pages météo"><a class="ha-secondary-button" href="{{ route('user.canicule.carte') }}">Carte des alertes</a><a class="ha-secondary-button" href="{{ route('user.canicule.alertes') }}">Alertes en cours</a></nav>
    </header>

    <section class="ha-admin-panel" aria-label="Choisir un gouvernorat">
        <form method="GET" action="{{ route('user.canicule.meteo') }}" class="flex flex-col gap-3 md:flex-row md:items-end">
            <label class="flex-1">
                <span class="ha-eyebrow">Gouvernorat</span>
                <select name="zone_id" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    @foreach($zones as $option)
                        <option value="{{ $option->id }}" @selected($selectedZoneId == $option->id)>{{ $option->nom }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="ha-primary-button">Afficher la météo</button>
        </form>
    </section>

    @if(! $zone)
        <p class="ha-notice ha-notice-error" role="status">Les données de ce gouvernorat ne sont pas encore disponibles.</p>
    @else
        @if($alerte)
            <section class="ha-canicule-citizen-alert is-{{ $alerte->niveau_gravite }}" role="status">
                <span class="pv-badge pv-niveau-{{ $alerte->niveau_gravite }}">{{ \App\Models\Alerte::NIVEAUX[$alerte->niveau_gravite] }}</span>
                <h2>{{ $alerte->titre }}</h2>
                <p>{{ $alerte->description }}</p>
                <small>Valide jusqu’au {{ $alerte->fin_validite->format('d/m/Y à H:i') }}</small>
            </section>
        @else
            <p class="ha-notice ha-notice-success" role="status">Aucune alerte canicule active pour {{ $zone->nom }}.</p>
        @endif

        @if($weather)
            <section class="ha-canicule-weather-grid" aria-label="Météo directe pour la zone sélectionnée">
                <article class="ha-admin-panel ha-canicule-weather-card">
                    <p class="ha-eyebrow">Météo directe</p>
                    <strong class="ha-canicule-temperature">{{ number_format((float) $weather['temperature'], 1, ',', ' ') }}°</strong>
                    <p>Humidité {{ $weather['humidity'] }} %</p>
                    <p>Vent {{ number_format((float) $weather['wind_speed'], 1, ',', ' ') }} km/h</p>
                    <p>Condition : {{ $weather['condition'] }}</p>
                    @if(! empty($weather['sunrise']))
                        <small>Lever {{ \Carbon\Carbon::createFromTimestamp($weather['sunrise'])->format('d/m/Y H:i') }}</small>
                    @endif
                </article>
            </section>
        @endif

    @endif
</div>
@endsection