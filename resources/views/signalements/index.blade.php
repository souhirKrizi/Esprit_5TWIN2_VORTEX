@extends('layouts.space', ['space' => 'user'])

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/heatalert/signalements.css') }}">
@endpush
@push('scripts')
<script src="{{ asset('assets/heatalert/signalements.js') }}" defer></script>
@endpush

@section('content')
<div class="ha-admin-page ha-signalements-page">
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Signalements citoyens</p>
            <h1>Mes signalements</h1>
            <p class="ha-muted">Suivez vos déclarations et leur état de validation.</p>
        </div>
        <a class="ha-primary-button" href="{{ route('user.signalements.create') }}">+ Nouveau signalement</a>
    </header>
    @if(session('success'))<div class="ha-notice ha-notice-success" role="status">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="ha-notice ha-notice-error" role="alert">{{ session('error') }}</div>@endif

    <section class="ha-admin-panel">
        <form class="ha-user-filters" method="GET" action="{{ route('user.signalements.index') }}">
            <label class="ha-select-field"><span class="sr-only">Statut</span><select name="statut"><option value="">Tous les statuts</option><option value="en_attente" @selected(request('statut') === 'en_attente')>En attente</option><option value="valide" @selected(request('statut') === 'valide')>Validé</option><option value="rejete" @selected(request('statut') === 'rejete')>Rejeté</option></select></label>
            <label class="ha-select-field"><span class="sr-only">Type</span><select name="type"><option value="">Tous les types</option><option value="incident" @selected(request('type') === 'incident')>Incident</option><option value="ressource_ok" @selected(request('type') === 'ressource_ok')>Ressource OK</option></select></label>
            <button class="ha-primary-button" type="submit">Filtrer</button>
            <a class="ha-text-button" href="{{ route('user.signalements.index') }}">Réinitialiser</a>
        </form>
        <div class="ha-table-wrap">
            <table class="ha-users-table ha-signalements-table">
                <thead><tr><th>Date</th><th>Type</th><th>Ressource</th><th>Photo</th><th>Commentaire</th><th>Statut</th><th>Analyse photo</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                @forelse($signalements as $signalement)
                    <tr>
                        <td>{{ $signalement->created_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $signalement->type_signalement === 'incident' ? 'Incident' : 'Ressource OK' }}</td>
                        <td>{{ $signalement->ressourceUtile?->nom ?: '—' }}</td>
                        <td>@if($signalement->piecesJointes->first())<img class="ha-list-thumbnail" src="{{ asset('storage/'.$signalement->piecesJointes->first()->chemin_fichier) }}" alt="Photo du signalement">@else—@endif</td>
                        <td>{{ Illuminate\Support\Str::limit($signalement->commentaire ?: 'Aucun commentaire', 70) }}</td>
                        <td><span class="ha-status-badge status-{{ $signalement->statut_validation }}">{{ ['en_attente' => 'En attente', 'valide' => 'Validé', 'rejete' => 'Rejeté'][$signalement->statut_validation] }}</span>@if($signalement->statut_validation === 'rejete' && $signalement->motif_rejet)<small class="ha-rejection-reason">{{ $signalement->motif_rejet }}</small>@endif</td>
                        <td>
                            @if($badge = $signalement->ia_image_badge)
                                <span class="ha-status-badge {{ $badge['class'] }}" title="{{ $signalement->ia_image_resume }}">
                                    {{ $badge['label'] }}
                                    @if($signalement->ia_image_confiance !== null)
                                        · {{ $signalement->ia_image_confiance }}%
                                    @endif
                                </span>
                                @if($signalement->ia_image_resume)
                                    <small class="ha-ia-table-resume">{{ Illuminate\Support\Str::limit($signalement->ia_image_resume, 45) }}</small>
                                @endif
                            @else
                                <span class="ha-muted">—</span>
                            @endif
                        </td>
                        <td><div class="ha-row-actions"><a href="{{ route('user.signalements.show', $signalement) }}">Voir</a>@if($signalement->statut_validation === 'en_attente')<a href="{{ route('user.signalements.edit', $signalement) }}">Modifier</a><button type="button" data-delete-trigger data-delete-action="{{ route('user.signalements.destroy', $signalement) }}">Supprimer</button>@endif</div></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="ha-empty-state"><strong>Aucun signalement pour le moment</strong><span>Votre premier signalement aidera les citoyens de votre région.</span><a class="ha-primary-button" href="{{ route('user.signalements.create') }}">Faire un signalement</a></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="ha-muted ha-ia-disclaimer-note"><small>Analyse automatique indicative, la décision finale revient à l'administrateur.</small></p>
        @if($signalements->hasPages())<div class="ha-pagination">{{ $signalements->links() }}</div>@endif
    </section>
</div>

<dialog class="ha-confirm-dialog" data-delete-dialog>
    <div class="ha-delete-symbol">!</div><h2>Supprimer ce signalement ?</h2><p>Cette action est définitive.</p>
    <form method="POST" data-delete-form>@csrf @method('DELETE')<footer class="ha-dialog-actions"><button class="ha-secondary-button" type="button" data-close-dialog>Annuler</button><button class="ha-danger-button" type="submit">Supprimer</button></footer></form>
</dialog>
@endsection
