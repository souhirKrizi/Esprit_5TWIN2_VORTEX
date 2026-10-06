@extends('layouts.space', ['space' => $espace])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page">
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Conseils de prévention</p>
            <h1>Mes équipements</h1>
            <p class="ha-muted">Ajoutez vos appareils sensibles à la chaleur ou aux coupures et décrivez leur problème.</p>
        </div>
        <a class="ha-primary-button" href="{{ route('prevention.equipements.create') }}">Ajouter un équipement</a>
    </header>

    @include('prevention.partials.messages')

    <section class="ha-admin-panel">
        <div class="ha-table-wrap">
            <table class="ha-users-table">
                <thead><tr><th>Équipement</th><th>Type</th><th>Sensible à</th><th>Autonomie</th><th>Conseils</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($equipements as $equipement)
                        <tr>
                            <td><strong>{{ $equipement->nom }}</strong></td>
                            <td>{{ $equipement->libelleType() }}</td>
                            <td>{{ $equipement->libelleSensibilite() }}</td>
                            <td>{{ $equipement->autonomie_heures === null ? '—' : $equipement->autonomie_heures.' h' }}</td>
                            <td><span class="ha-muted">Aucun</span></td>
                            <td>
                                <div class="ha-row-actions">
                                    <a href="{{ route('prevention.equipements.show', $equipement) }}">Voir</a>
                                    <a href="{{ route('prevention.equipements.edit', $equipement) }}">Modifier</a>
                                    <form class="pv-inline-form" method="POST" action="{{ route('prevention.equipements.destroy', $equipement) }}" data-confirm="Supprimer l’équipement « {{ $equipement->nom }} » ?">
                                        @csrf @method('DELETE')
                                        <button class="pv-link pv-link-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="ha-empty-state">Vous n’avez encore ajouté aucun équipement.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
