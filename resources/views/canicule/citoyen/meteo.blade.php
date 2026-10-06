@extends('layouts.space', ['space' => 'user'])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page ha-canicule-admin">
    <header class="ha-admin-heading">
        <div><p class="ha-eyebrow">Météo et canicule</p><h1>Météo de ma zone</h1><p class="ha-muted">{{ $zone ? $zone->nom : auth()->user()->governorate }}</p></div>
        <nav class="ha-canicule-actions" aria-label="Pages météo"><a class="ha-secondary-button" href="{{ route('user.canicule.carte') }}">Carte des alertes</a><a class="ha-secondary-button" href="{{ route('user.canicule.alertes') }}">Alertes en cours</a></nav>
    </header>

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

        <section class="ha-canicule-weather-grid" aria-label="Dernières données météo">
            <article class="ha-admin-panel ha-canicule-weather-card">
                <p class="ha-eyebrow">Dernière observation</p>
                @if($observation)
                    <strong class="ha-canicule-temperature">{{ number_format((float) $observation->temperature, 1, ',', ' ') }}°</strong>
                    <p>Humidité {{ $observation->humidite }} %</p>
                    @if($observation->vitesse_vent !== null)<p>Vent {{ number_format((float) $observation->vitesse_vent, 1, ',', ' ') }} km/h</p>@endif
                    @if($observation->indice_uv !== null)<p>Indice UV {{ $observation->indice_uv }}</p>@endif
                    <small>Mesurée le {{ $observation->date_mesure->format('d/m/Y à H:i') }} · {{ $observation->source }}</small>
                @else
                    <p class="ha-muted">Aucune observation enregistrée pour le moment.</p>
                @endif
            </article>
            <article class="ha-admin-panel ha-canicule-weather-card">
                <p class="ha-eyebrow">Dernière prévision</p>
                @if($prevision)
                    <strong class="ha-canicule-temperature">{{ number_format((float) $prevision->temperature, 1, ',', ' ') }}°</strong>
                    <p>Humidité prévue {{ $prevision->humidite }} %</p>
                    @if($prevision->vitesse_vent !== null)<p>Vent {{ number_format((float) $prevision->vitesse_vent, 1, ',', ' ') }} km/h</p>@endif
                    @if($prevision->indice_uv !== null)<p>Indice UV {{ $prevision->indice_uv }}</p>@endif
                    <small>Prévue le {{ $prevision->date_mesure->format('d/m/Y à H:i') }} · {{ $prevision->source }}</small>
                @else
                    <p class="ha-muted">Aucune prévision enregistrée pour le moment.</p>
                @endif
            </article>
        </section>
    @endif
</div>
@endsection