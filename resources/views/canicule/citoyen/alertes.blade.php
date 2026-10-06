@extends('layouts.space', ['space' => 'user'])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page ha-canicule-admin">
    <header class="ha-admin-heading">
        <div><p class="ha-eyebrow">Météo et canicule</p><h1>Alertes en cours</h1><p class="ha-muted">{{ $zone ? 'Votre gouvernorat : '.$zone->nom : 'Alertes actives dans les gouvernorats' }}</p></div>
        <nav class="ha-canicule-actions" aria-label="Pages météo"><a class="ha-secondary-button" href="{{ route('user.canicule.carte') }}">Carte des alertes</a><a class="ha-secondary-button" href="{{ route('user.canicule.meteo') }}">Météo de ma zone</a></nav>
    </header>
    @if($alertes->isEmpty())
        <p class="ha-notice ha-notice-success" role="status">Aucune alerte active pour le moment.</p>
    @else
        <section class="ha-canicule-alert-list" aria-label="Alertes actives">
            @foreach($alertes as $alerte)
                <article class="ha-admin-panel ha-canicule-citizen-alert is-{{ $alerte->niveau_gravite }}">
                    <div class="pv-tags"><span class="pv-badge pv-niveau-{{ $alerte->niveau_gravite }}">{{ \App\Models\Alerte::NIVEAUX[$alerte->niveau_gravite] }}</span><span class="pv-badge">{{ $alerte->zone->nom }}</span></div>
                    <h2>{{ $alerte->titre }}</h2>
                    <p>{{ $alerte->description }}</p>
                    <small>Valide du {{ $alerte->debut_validite->format('d/m/Y à H:i') }} au {{ $alerte->fin_validite->format('d/m/Y à H:i') }}</small>
                </article>
            @endforeach
        </section>
    @endif
</div>
@endsection