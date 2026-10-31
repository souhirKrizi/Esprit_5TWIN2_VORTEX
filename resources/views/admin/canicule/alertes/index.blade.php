@extends('layouts.space', ['space' => 'admin'])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page ha-canicule-admin">
    <header class="ha-admin-heading"><div><p class="ha-eyebrow">Administration / Météo et canicule</p><h1>Alertes</h1><p class="ha-muted">Créez des alertes manuelles par gouvernorat et période de validité.</p></div><a class="ha-primary-button" href="{{ route('admin.canicule.alertes.create') }}">Créer une alerte</a></header>
    @include('admin.canicule.partials.messages')
    <section class="ha-admin-panel"><p><a href="{{ route('admin.canicule.carte') }}">Voir la carte colorée</a></p><div class="pv-table-wrap"><table class="pv-table"><thead><tr><th>Alerte</th><th>Gouvernorat</th><th>Gravité</th><th>Validité</th><th>Statut</th><th>Actions</th></tr></thead><tbody>
        @forelse($alertes as $alerte)
            <tr><td>{{ $alerte->titre }}</td><td>{{ $alerte->zone->nom }}</td><td>{{ \App\Models\Alerte::NIVEAUX[$alerte->niveau_gravite] }}</td><td>{{ $alerte->debut_validite->format('d/m/Y H:i') }} – {{ $alerte->fin_validite->format('d/m/Y H:i') }}</td><td>{{ \App\Models\Alerte::STATUTS[$alerte->statut] }}</td><td class="pv-table-actions"><a href="{{ route('admin.canicule.alertes.edit', $alerte) }}">Modifier</a><form method="POST" action="{{ route('admin.canicule.alertes.destroy', $alerte) }}" onsubmit="return confirm('Supprimer cette alerte ?')">@csrf @method('DELETE')<button type="submit">Supprimer</button></form></td></tr>
        @empty<tr><td colspan="6">Aucune alerte enregistrée.</td></tr>@endforelse
        </tbody></table></div>{{ $alertes->links() }}</section>
</div>
@endsection