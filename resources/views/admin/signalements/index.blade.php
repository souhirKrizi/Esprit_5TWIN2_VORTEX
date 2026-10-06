@extends('layouts.space', ['space' => $space])

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/heatalert/signalements.css') }}">
@endpush
@push('scripts')
<script src="{{ asset('assets/heatalert/signalements.js') }}" defer></script>
@endpush

@section('content')
<div class="ha-admin-page ha-signalements-page">
    <header class="ha-admin-heading">
        <div><p class="ha-eyebrow">Administration / Signalements citoyens</p><h1>Modérer les signalements</h1><p class="ha-muted">Consultez, validez ou rejetez les déclarations reçues.</p></div>
        <a class="ha-secondary-button" href="{{ route('admin.signalements.carte') }}">Voir la carte</a>
    </header>
    @if(session('success'))<div class="ha-notice ha-notice-success" role="status">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="ha-notice ha-notice-error" role="alert">{{ session('error') }}</div>@endif
    <section class="ha-admin-panel">
        <form class="ha-user-filters" method="GET" action="{{ route('admin.signalements.index') }}">
            <label class="ha-search-field"><span class="sr-only">Rechercher</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Citoyen, description ou incident"></label>
            <label class="ha-select-field"><span class="sr-only">Statut</span><select name="statut"><option value="">Tous les statuts</option><option value="en_attente" @selected(request('statut') === 'en_attente')>En attente</option><option value="valide" @selected(request('statut') === 'valide')>Validés</option><option value="rejete" @selected(request('statut') === 'rejete')>Rejetés</option></select></label>
            <label class="ha-select-field"><span class="sr-only">Type</span><select name="type"><option value="">Tous les types</option><option value="incident" @selected(request('type') === 'incident')>Incidents</option><option value="ressource_ok" @selected(request('type') === 'ressource_ok')>Ressources OK</option></select></label>
            <button class="ha-primary-button" type="submit">Filtrer</button>
            <a class="ha-text-button" href="{{ route('admin.signalements.index') }}">Réinitialiser</a>
        </form>
        <div class="ha-table-wrap"><table class="ha-users-table ha-signalements-table"><thead><tr><th>Date</th><th>Citoyen</th><th>Type</th><th>Ressource</th><th>Photo</th><th>Statut</th><th>Actions</th></tr></thead><tbody>
        @forelse($signalements as $signalement)
            <tr><td>{{ $signalement->created_at?->format('d/m/Y H:i') }}</td><td>{{ $signalement->user?->name ?: '—' }}</td><td>{{ $signalement->type_signalement === 'incident' ? 'Incident' : 'Ressource OK' }}</td><td>{{ $signalement->ressourceUtile?->nom ?: '—' }}</td><td>@if($signalement->piecesJointes->first())<img class="ha-list-thumbnail" src="{{ asset('storage/'.$signalement->piecesJointes->first()->chemin_fichier) }}" alt="Photo">@else—@endif</td><td><span class="ha-status-badge status-{{ $signalement->statut_validation }}">{{ ['en_attente' => 'En attente', 'valide' => 'Validé', 'rejete' => 'Rejeté'][$signalement->statut_validation] }}</span></td><td><a class="ha-text-button" href="{{ route('admin.signalements.show', $signalement) }}">Voir / traiter</a></td></tr>
        @empty
            <tr><td colspan="7" class="ha-empty-state">Aucun signalement ne correspond aux filtres.</td></tr>
        @endforelse
        </tbody></table></div>
        @if($signalements->hasPages())<div class="ha-pagination">{{ $signalements->links() }}</div>@endif
    </section>
</div>
@endsection
