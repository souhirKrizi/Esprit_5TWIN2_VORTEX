@extends('layouts.space', ['space' => 'admin'])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page ha-canicule-admin">
    <header class="ha-admin-heading"><div><p class="ha-eyebrow">Administration / Météo et canicule</p><h1>Gouvernorats</h1><p class="ha-muted">Les 24 gouvernorats et leurs limites cartographiques.</p></div><a class="ha-secondary-button" href="{{ route('admin.canicule.carte') }}">Voir la carte</a></header>
    @include('admin.canicule.partials.messages')
    <section class="ha-admin-panel"><div class="pv-table-wrap"><table class="pv-table"><thead><tr><th>Gouvernorat</th><th>Code</th><th>Relevés</th><th>Alertes</th><th>État</th><th>Action</th></tr></thead><tbody>
        @forelse($zones as $zone)
            <tr><td><strong>{{ $zone->nom }}</strong></td><td>{{ $zone->code }}</td><td>{{ $zone->releves_meteo_count }}</td><td>{{ $zone->alertes_count }}</td><td><span class="pv-badge {{ $zone->active ? 'pv-statut-publiee' : 'pv-statut-brouillon' }}">{{ $zone->active ? 'Active' : 'Désactivée' }}</span></td><td class="pv-table-actions"><a href="{{ route('admin.canicule.zones.edit', $zone) }}">Gérer l’état</a></td></tr>
        @empty<tr><td colspan="6">Aucune zone enregistrée.</td></tr>@endforelse
        </tbody></table></div>
        {{ $zones->links() }}
    </section>
</div>
@endsection