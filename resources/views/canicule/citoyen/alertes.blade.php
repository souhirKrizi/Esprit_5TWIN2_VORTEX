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

    @if($evenements->isNotEmpty())
        <section class="mt-8" aria-label="Campagnes disponibles avec conseils">
            <div class="mb-4">
                <p class="ha-eyebrow">Conseils de sécurité</p>
                <h2 class="text-xl font-semibold">Campagnes disponibles</h2>
            </div>

            <div class="ha-canicule-alert-list">
                @foreach($evenements as $item)
                    @php($campagne = $item['campagne'])
                    @php($info = $item['info'])
                    <article class="ha-admin-panel ha-canicule-citizen-alert is-{{ in_array(($info['risque'] ?? 'canicule'), ['canicule','inondations','pluie'], true) ? 'orange' : 'green' }}">
                        <div class="pv-tags">
                            <span class="pv-badge">{{ $campagne->date_debut?->format('d/m/Y') }}</span>
                            <span class="pv-badge">{{ $campagne->lieu ?: 'Lieu à préciser' }}</span>
                            <span class="pv-badge">{{ ucfirst($info['risque'] ?? 'canicule') }}</span>
                        </div>
                        <h2>{{ $campagne->titre }}</h2>
                        <p>{{ $campagne->description }}</p>
                        <p class="mt-3"><strong>Info :</strong> {{ $info['info'] ?? 'Aucune information disponible.' }}</p>
                        <div class="mt-4 flex items-center justify-between gap-3">
                            <span class="text-sm font-medium text-slate-700">Risque estimé : {{ ucfirst($info['risque'] ?? 'canicule') }}</span>
                            <form method="GET" action="{{ route('user.canicule.alertes') }}">
                                <input type="hidden" name="refresh_campaign" value="{{ $campagne->id }}">
                                <button type="submit" class="ha-secondary-button">Actualiser</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection