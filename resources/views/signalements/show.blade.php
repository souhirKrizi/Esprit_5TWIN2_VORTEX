@extends('layouts.space', ['space' => $space])

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<link rel="stylesheet" href="{{ asset('assets/heatalert/signalements.css') }}">
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
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
        </section>

        <section class="ha-admin-panel ha-detail-card">
            <h2>Emplacement</h2>
            <div class="ha-detail-map" data-static-map data-lat="{{ $signalement->latitude }}" data-lng="{{ $signalement->longitude }}"></div>
        </section>
    </div>
</div>
@endsection
