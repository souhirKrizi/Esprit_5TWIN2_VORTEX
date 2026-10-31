@extends('layouts.space', ['space' => 'admin'])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page">
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Administration / Conseils de prévention</p>
            <h1>Fiches conseils</h1>
            <p class="ha-muted">Ajoutez, modifiez et publiez les conseils pour les habitants.</p>
        </div>
        <a class="ha-primary-button" href="{{ route('admin.prevention.fiches.create') }}">Ajouter une fiche</a>
    </header>

    @include('prevention.partials.messages')

    <section class="ha-admin-panel pv-box-alerte">
        <form class="ha-user-filters" method="POST" action="{{ route('admin.prevention.alerte.update') }}">
            @csrf @method('PUT')
            <label for="niveau"><strong>Alerte en cours</strong></label>
            <label class="ha-select-field">
                <select id="niveau" name="niveau">
                    <option value="">Aucune alerte</option>
                    @foreach(\App\Models\FicheConseil::NIVEAUX as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(old('niveau', $alerteEnCours) === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </label>
            <button class="ha-secondary-button" type="submit">Enregistrer</button>
            <span class="pv-info">Les conseils de ce niveau s’affichent en priorité pour les habitants.</span>
        </form>
        @error('niveau')<p class="pv-erreur pv-erreur-alerte">{{ $message }}</p>@enderror
    </section>

    <section class="ha-admin-panel">
        <form class="ha-user-filters" method="GET" action="{{ route('admin.prevention.fiches.index') }}">
            <label class="ha-search-field"><span class="sr-only">Rechercher</span><x-space-icon name="file" /><input type="search" name="recherche" value="{{ $filtres['recherche'] ?? '' }}" placeholder="Rechercher par titre"></label>
            <label class="ha-select-field"><span class="sr-only">Statut</span>
                <select name="statut">
                    <option value="">Tous les statuts</option>
                    @foreach(\App\Models\FicheConseil::STATUTS as $valeur => $libelle)
                        <option value="{{ $valeur }}" @selected(($filtres['statut'] ?? '') === $valeur)>{{ $libelle }}</option>
                    @endforeach
                </select>
            </label>
            <button class="ha-secondary-button" type="submit">Rechercher</button>
            @if($filtres)<a class="ha-text-button" href="{{ route('admin.prevention.fiches.index') }}">Effacer</a>@endif
        </form>

        <div class="ha-table-wrap">
            <table class="ha-users-table">
                <thead><tr><th>Titre</th><th>Thématique</th><th>Niveau d’alerte</th><th>Gestes</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($fiches as $fiche)
                        <tr>
                            <td><strong style="color:#111d35">{{ $fiche->titre }}</strong></td>
                            <td>{{ $fiche->libelleThematique() }}</td>
                            <td><span class="pv-badge pv-niveau-{{ $fiche->niveau_alerte }}">{{ $fiche->libelleNiveau() }}</span></td>
                            <td>{{ $fiche->gestes_count }}</td>
                            <td><span class="pv-badge pv-statut-{{ $fiche->statut }}">{{ $fiche->libelleStatut() }}</span></td>
                            <td>
                                <div class="ha-row-actions">
                                    <a href="{{ route('admin.prevention.fiches.show', $fiche) }}">Voir</a>
                                    <a href="{{ route('admin.prevention.fiches.edit', $fiche) }}">Modifier</a>
                                    <form class="pv-inline-form" method="POST" action="{{ route('admin.prevention.fiches.destroy', $fiche) }}" data-confirm="Supprimer la fiche « {{ $fiche->titre }} » ?">
                                        @csrf @method('DELETE')
                                        <button class="pv-link pv-link-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="ha-empty-state">Aucune fiche trouvée.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($fiches->hasPages())<div class="ha-pagination">{{ $fiches->links() }}</div>@endif
    </section>
</div>
@endsection
