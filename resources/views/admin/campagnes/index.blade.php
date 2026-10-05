@extends('layouts.space', ['space' => 'admin'])

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/heatalert/campagnes.css') }}">
@endpush
@push('scripts')
<script src="{{ asset('assets/heatalert/campagnes.js') }}" defer></script>
@endpush

@section('content')
<div class="ha-admin-page" data-campaign-page>
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Administration / Campagnes</p>
            <h1>Campagnes de prévention</h1>
            <p class="ha-muted">Organisez les actions sur le terrain et affectez un agent à chaque campagne.</p>
        </div>
        <button class="ha-primary-button ha-campaign-add" type="button" data-campaign-add aria-haspopup="dialog"><x-space-icon name="plus" />Ajouter une campagne</button>
    </header>

    <div class="ha-campaign-summary" aria-label="Résumé des campagnes">
        <div><span>Total des campagnes</span><strong data-campaign-total>0</strong></div>
        <div><span>En cours</span><strong data-campaign-active>0</strong></div>
        <div><span>Agents affectés</span><strong data-campaign-agents>0</strong></div>
    </div>
    <p class="ha-campaign-demo">Aperçu avec des données de démonstration.</p>
    <p class="ha-notice ha-notice-success" role="status" data-campaign-notice hidden></p>

    <section class="ha-admin-panel" aria-label="Liste des campagnes">
        <div class="ha-user-filters">
            <label class="ha-search-field"><span class="sr-only">Rechercher une campagne</span><x-space-icon name="flag" /><input type="search" placeholder="Rechercher un titre, un lieu, un agent…" data-campaign-search></label>
            <label class="ha-select-field"><span class="sr-only">Filtrer par statut</span><select data-campaign-status><option value="">Tous les statuts</option><option value="brouillon">Brouillon</option><option value="publiee">Publiée</option><option value="en_cours">En cours</option><option value="terminee">Terminée</option><option value="annulee">Annulée</option></select></label>
            <span class="ha-campaign-results" data-campaign-results></span>
        </div>
        <div class="ha-table-wrap">
            <table class="ha-users-table ha-campaign-table">
                <thead><tr><th>Campagne</th><th>Agent responsable</th><th>Période</th><th>Statut</th><th>Actions</th></tr></thead>
                <tbody data-campaign-list></tbody>
            </table>
        </div>
    </section>

    <dialog class="ha-campaign-dialog" data-campaign-create aria-labelledby="campaign-create-title" aria-describedby="campaign-create-description">
        <header class="ha-dialog-heading"><div><p class="ha-eyebrow">Nouvelle action</p><h2 id="campaign-create-title">Ajouter une campagne</h2></div><button class="ha-icon-button" type="button" data-dialog-close aria-label="Fermer le formulaire"><x-space-icon name="close" /></button></header>
        <p class="ha-muted" id="campaign-create-description">Définissez la campagne et choisissez son agent responsable.</p>
        <form class="ha-campaign-form" data-campaign-form>
            <div class="ha-form-grid">
                <label class="ha-field-wide">Titre<input name="titre" maxlength="255" placeholder="Ex. : Prévention canicule à Tunis" required autofocus></label>
                <label class="ha-field-wide">Description<textarea name="description" rows="3" placeholder="Présentez les actions prévues…" required></textarea></label>
                <label class="ha-field-wide">Objectif<textarea name="objectif" rows="2" placeholder="Quel résultat souhaitez-vous atteindre ?" required></textarea></label>
                <label>Lieu<input name="lieu" maxlength="255" placeholder="Ville ou lieu d’intervention" required></label>
                <label>Agent responsable<select name="organisateur_id" required><option value="">Choisir un agent de campagne</option><option value="demo-agent-1">Amira Ben Ali</option><option value="demo-agent-2">Youssef Mansour</option><option value="demo-agent-3">Inès Trabelsi</option></select><small>Agents de démonstration ayant le rôle agent-campagne.</small></label>
                <label>Date de début<input type="date" name="date_debut" required></label>
                <label>Date de fin<input type="date" name="date_fin" required></label>
                <label>Statut<select name="statut" required><option value="brouillon">Brouillon</option><option value="publiee">Publiée</option><option value="en_cours">En cours</option><option value="terminee">Terminée</option><option value="annulee">Annulée</option></select></label>
                <label>Image <span class="ha-optional">(facultative)</span><input type="file" name="image" accept="image/*"></label>
            </div>
            <footer class="ha-dialog-actions"><button class="ha-secondary-button" type="button" data-dialog-close>Annuler</button><button class="ha-primary-button" type="submit">Ajouter la campagne</button></footer>
        </form>
    </dialog>

    <dialog class="ha-campaign-dialog ha-confirm-dialog" data-campaign-delete aria-labelledby="campaign-delete-title" aria-describedby="campaign-delete-description">
        <div class="ha-delete-symbol"><x-space-icon name="alert" /></div>
        <h2 id="campaign-delete-title">Supprimer cette campagne ?</h2>
        <p id="campaign-delete-description">La campagne « <strong data-campaign-delete-name></strong> » sera retirée de la liste.</p>
        <footer class="ha-dialog-actions"><button class="ha-secondary-button" type="button" data-dialog-close autofocus>Annuler</button><button class="ha-danger-button" type="button" data-campaign-confirm-delete>Supprimer la campagne</button></footer>
    </dialog>
</div>
@endsection
