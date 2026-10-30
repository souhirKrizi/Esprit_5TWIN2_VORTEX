@extends('layouts.space', ['space' => $space])

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/heatalert/signalements.css') }}">
@endpush

@section('content')
<div class="ha-admin-page ha-signalements-page">
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Communauté citoyenne</p>
            <h1>Signalements validés</h1>
            <p class="ha-muted">Consultez les incidents et confirmations partagés par les autres citoyens.</p>
        </div>
        <a class="ha-secondary-button" href="{{ route('user.signalements.carte') }}">Retour à la carte</a>
    </header>
    <section class="ha-admin-panel">
        <form class="ha-user-filters" method="GET" action="{{ route('user.signalements.public.index') }}">
            <label class="ha-select-field"><span class="sr-only">Type</span><select name="type"><option value="">Tous les types</option><option value="incident" @selected(request('type') === 'incident')>Incidents</option><option value="ressource_ok" @selected(request('type') === 'ressource_ok')>Ressources OK</option></select></label>
            <button class="ha-primary-button" type="submit">Filtrer</button>
            <a class="ha-text-button" href="{{ route('user.signalements.public.index') }}">Réinitialiser</a>
        </form>
        <div class="ha-public-report-grid">
            @forelse($signalements as $signalement)
                <article class="ha-public-report-card">
                    @if($signalement->piecesJointes->first())<img src="{{ asset('storage/'.$signalement->piecesJointes->first()->chemin_fichier) }}" alt="Photo du signalement">@endif
                    <div class="ha-public-report-body">
                        <div class="ha-public-report-meta"><span class="ha-status-badge status-valide">Validé</span><time>{{ $signalement->created_at?->format('d/m/Y') }}</time></div>
                        <h2>{{ $signalement->type_signalement === 'incident' ? 'Incident signalé' : 'Ressource opérationnelle' }}</h2>
                        <p>{{ $signalement->commentaire ?: 'Aucune description.' }}</p>
                        <small>{{ $signalement->ressourceUtile?->nom ?: 'Position citoyenne' }}</small>
                        <a class="ha-text-button" href="{{ route('user.signalements.public.show', $signalement) }}">Voir le détail</a>
                    </div>
                </article>
            @empty
                <p class="ha-empty-state">Aucun signalement validé d’un autre citoyen pour le moment.</p>
            @endforelse
        </div>
        @if($signalements->hasPages())<div class="ha-pagination">{{ $signalements->links() }}</div>@endif
    </section>
</div>
@endsection
