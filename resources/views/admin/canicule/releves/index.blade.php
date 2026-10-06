@extends('layouts.space', ['space' => 'admin'])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page ha-canicule-admin">
    <header class="ha-admin-heading"><div><p class="ha-eyebrow">Administration / Météo et canicule</p><h1>Relevés météo</h1><p class="ha-muted">Saisie manuelle temporaire avant le branchement d’une API météo.</p></div><a class="ha-primary-button" href="{{ route('admin.canicule.releves.create') }}">Ajouter un relevé</a></header>
    @include('admin.canicule.partials.messages')
    <section class="ha-admin-panel"><div class="pv-table-wrap"><table class="pv-table"><thead><tr><th>Date</th><th>Gouvernorat</th><th>Type</th><th>Température</th><th>Humidité</th><th>Vent</th><th>UV</th><th>Actions</th></tr></thead><tbody>
        @forelse($releves as $releve)
            <tr><td>{{ $releve->date_mesure->format('d/m/Y H:i') }}</td><td>{{ $releve->zone->nom }}</td><td>{{ \App\Models\ReleveMeteo::TYPES[$releve->type] }}</td><td>{{ $releve->temperature }} °C</td><td>{{ $releve->humidite }} %</td><td>{{ $releve->vitesse_vent ? $releve->vitesse_vent.' km/h' : '—' }}</td><td>{{ $releve->indice_uv ?? '—' }}</td><td class="pv-table-actions"><a href="{{ route('admin.canicule.releves.edit', $releve) }}">Modifier</a><form method="POST" action="{{ route('admin.canicule.releves.destroy', $releve) }}" onsubmit="return confirm('Supprimer ce relevé ?')">@csrf @method('DELETE')<button type="submit">Supprimer</button></form></td></tr>
        @empty<tr><td colspan="8">Aucun relevé météo enregistré.</td></tr>@endforelse
        </tbody></table></div>{{ $releves->links() }}</section>
</div>
@endsection