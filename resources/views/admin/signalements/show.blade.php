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
            <p class="ha-eyebrow">Administration / Signalement</p>
            <h1>{{ $signalement->type_signalement === 'incident' ? 'Incident citoyen' : 'Ressource confirmée' }}</h1>
            <p class="ha-muted">Soumis par {{ $signalement->user?->name ?: 'Citoyen' }} le {{ $signalement->created_at?->format('d/m/Y à H:i') }}</p>
        </div>
        <a class="ha-text-button" href="{{ route('admin.signalements.index') }}">Retour à la liste</a>
    </header>

    @if(session('success'))
        <div class="ha-notice ha-notice-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="ha-detail-grid">
        <section class="ha-admin-panel ha-detail-card">
            <div class="ha-detail-heading">
                <span class="ha-status-badge status-{{ $signalement->statut_validation }}">{{ ['en_attente' => 'En attente', 'valide' => 'Validé', 'rejete' => 'Rejeté'][$signalement->statut_validation] }}</span>
            </div>
            <dl class="ha-detail-list">
                <div><dt>Citoyen</dt><dd>{{ $signalement->user?->name }} · {{ $signalement->user?->email }}</dd></div>
                <div><dt>Type</dt><dd>{{ $signalement->type_signalement === 'incident' ? 'Incident' : 'Tout fonctionne' }}</dd></div>
                @if($signalement->type_incident)
                    <div><dt>Incident</dt><dd>{{ ['coupure_electricite' => 'Coupure d’électricité', 'point_eau_hors_service' => 'Point d’eau hors service', 'centre_ferme' => 'Centre fermé', 'autre' => 'Autre'][$signalement->type_incident] }}</dd></div>
                @endif
                <div><dt>Ressource</dt><dd>{{ $signalement->ressourceUtile?->nom ?: 'Aucune ressource précise' }}</dd></div>
                <div><dt>Description</dt><dd>{{ $signalement->commentaire ?: 'Aucune description.' }}</dd></div>
                <div><dt>Coordonnées</dt><dd>{{ $signalement->latitude }}, {{ $signalement->longitude }}</dd></div>
            </dl>
            @if($signalement->piecesJointes->isNotEmpty())
                <div class="ha-attachment-grid">
                    @foreach($signalement->piecesJointes as $pieceJointe)
                        <figure class="ha-attachment"><img src="{{ asset('storage/'.$pieceJointe->chemin_fichier) }}" alt="{{ $pieceJointe->nom_original }}"><figcaption>{{ $pieceJointe->nom_original }}</figcaption></figure>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="ha-admin-panel ha-detail-card">
            <h2>Décision de modération</h2>
            @if($signalement->statut_validation === 'en_attente')
                <form method="POST" action="{{ route('admin.signalements.validate', $signalement) }}" class="ha-admin-decision-form">
                    @csrf
                    <button class="ha-primary-button" type="submit">Valider le signalement</button>
                </form>
                <form method="POST" action="{{ route('admin.signalements.reject', $signalement) }}" class="ha-admin-decision-form">
                    @csrf
                    <label for="motif_rejet">Motif du rejet</label>
                    <textarea id="motif_rejet" name="motif_rejet" rows="5" minlength="10" maxlength="1000" required>{{ old('motif_rejet') }}</textarea>
                    @error('motif_rejet')<p class="ha-field-error">{{ $message }}</p>@enderror
                    <button class="ha-danger-button" type="submit">Rejeter le signalement</button>
                </form>
            @elseif($signalement->motif_rejet)
                <div class="ha-rejection-box"><strong>Motif du rejet</strong><p>{{ $signalement->motif_rejet }}</p></div>
            @else
                <p class="ha-notice ha-notice-success">Ce signalement a été validé.</p>
            @endif
        </section>
    </div>

    <section class="ha-admin-panel ha-detail-card ha-admin-location">
        <h2>Emplacement</h2>
        <div class="ha-detail-map" data-static-map data-lat="{{ $signalement->latitude }}" data-lng="{{ $signalement->longitude }}"></div>
    </section>
</div>
@endsection
