@extends('layouts.space', ['space' => $space])

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
<link rel="stylesheet" href="{{ asset('assets/heatalert/signalements.css') }}">
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
<script src="{{ asset('assets/heatalert/signalements.js') }}" defer></script>
@endpush

@section('content')
<div class="ha-admin-page ha-signalements-page">
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Détail du signalement</p>
            <h1>{{ $signalement->type_signalement === 'incident' ? 'Incident signalé' : 'Ressource opérationnelle' }}</h1>
            <p class="ha-muted">Envoyé le {{ $signalement->created_at?->format('d/m/Y à H:i') }}</p>
        </div>
        <a class="ha-text-button" href="{{ route('user.signalements.index') }}">Retour à mes signalements</a>
    </header>

    @if(session('success'))
        <div class="ha-notice ha-notice-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="ha-detail-grid">
        <section class="ha-admin-panel ha-detail-card">
            <div class="ha-detail-heading">
                <span class="ha-status-badge status-{{ $signalement->statut_validation }}">
                    {{ ['en_attente' => 'En attente', 'valide' => 'Validé', 'rejete' => 'Rejeté'][$signalement->statut_validation] }}
                </span>
                @if($signalement->statut_validation === 'en_attente')
                    <a class="ha-secondary-button" href="{{ route('user.signalements.edit', $signalement) }}">Modifier</a>
                @endif
            </div>

            <dl class="ha-detail-list">
                <div>
                    <dt>Type</dt>
                    <dd>{{ $signalement->type_signalement === 'incident' ? 'Incident' : 'Tout fonctionne' }}</dd>
                </div>
                @if($signalement->type_incident)
                    <div>
                        <dt>Incident</dt>
                        <dd>{{ ['coupure_electricite' => 'Coupure d’électricité', 'point_eau_hors_service' => 'Point d’eau hors service', 'centre_ferme' => 'Centre fermé', 'autre' => 'Autre'][$signalement->type_incident] }}</dd>
                    </div>
                @endif
                <div>
                    <dt>Ressource</dt>
                    <dd>{{ $signalement->ressourceUtile?->nom ?: 'Aucune ressource précise' }}</dd>
                </div>
                <div>
                    <dt>Commentaire</dt>
                    <dd>{{ $signalement->commentaire ?: 'Aucun commentaire' }}</dd>
                </div>
                <div>
                    <dt>Position</dt>
                    <dd>{{ $signalement->latitude }}, {{ $signalement->longitude }}</dd>
                </div>
            </dl>

            @if($signalement->statut_validation === 'rejete' && $signalement->motif_rejet)
                <div class="ha-rejection-box">
                    <strong>Motif du rejet</strong>
                    <p>{{ $signalement->motif_rejet }}</p>
                </div>
            @endif

            @if($signalement->piecesJointes->isNotEmpty())
                <div class="ha-attachment-grid">
                    @foreach($signalement->piecesJointes as $pieceJointe)
                        <figure class="ha-attachment">
                            <img src="{{ asset('storage/'.$pieceJointe->chemin_fichier) }}" alt="{{ $pieceJointe->nom_original }}">
                            <figcaption>{{ $pieceJointe->nom_original }}</figcaption>
                        </figure>
                    @endforeach
                </div>
            @endif

            @if($signalement->piecesJointes->isNotEmpty() || $signalement->ia_image_statut !== 'non_analyse')
                <div class="ha-ia-image-box">
                    <div class="ha-ia-image-header">
                        <strong>Analyse automatique de la photo</strong>
                        @if($badge = $signalement->ia_image_badge)
                            <span class="ha-status-badge {{ $badge['class'] }}">
                                {{ $badge['label'] }}
                                @if($signalement->ia_image_confiance !== null)
                                    · {{ $signalement->ia_image_confiance }}%
                                @endif
                            </span>
                        @endif
                    </div>
                    @if($signalement->ia_image_resume)
                        <p class="ha-ia-image-resume">{{ $signalement->ia_image_resume }}</p>
                    @endif
                    <p class="ha-ia-image-disclaimer ha-muted">
                        Analyse automatique indicative, la décision finale revient à l'administrateur.
                    </p>
                    @if($signalement->ia_image_statut === 'indisponible')
                        <form method="POST" action="{{ route('user.signalements.analyse-image', $signalement) }}" class="ha-ia-retry-form">
                            @csrf
                            <button type="submit" class="ha-secondary-button">Relancer l'analyse</button>
                        </form>
                    @endif
                </div>
            @endif
        </section>

        <section class="ha-admin-panel ha-detail-card">
            <h2>Emplacement</h2>
            <div class="ha-detail-map" data-static-map data-lat="{{ $signalement->latitude }}" data-lng="{{ $signalement->longitude }}"></div>
        </section>
    </div>
</div>
@endsection
