@extends('layouts.space', ['space' => 'admin'])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page ha-canicule-admin">
    <header class="ha-admin-heading"><div><p class="ha-eyebrow">Administration / Météo et canicule</p><h1>Relevés météo</h1><p class="ha-muted">Sélectionnez un gouvernorat pour afficher la météo en direct depuis l’API.</p></div><a class="ha-primary-button" href="{{ route('admin.canicule.releves.create') }}">Ajouter un relevé</a></header>
    @include('admin.canicule.partials.messages')

    <section class="ha-admin-panel">
        <form method="GET" action="{{ route('admin.canicule.releves.index') }}" class="flex flex-col gap-3 md:flex-row md:items-end">
            <label class="flex-1">
                <span class="ha-eyebrow">Gouvernorat</span>
                <select name="zone_id" class="w-full rounded-xl border border-slate-300 px-3 py-2">
                    @foreach($zones as $zone)
                        <option value="{{ $zone->id }}" @selected($selectedZone && $selectedZone->id === $zone->id)>{{ $zone->nom }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="ha-primary-button">Afficher</button>
        </form>
    </section>

    @if($selectedZone)
        <section class="ha-admin-panel">
            <h2 class="mb-3 text-xl font-semibold">Météo directe — {{ $selectedZone->nom }}</h2>
            @if($weather)
                <div class="grid gap-4 md:grid-cols-3">
                    <article>
                        <p class="ha-eyebrow">Température</p>
                        <strong class="text-3xl font-bold">{{ number_format((float) $weather['temperature'], 1, ',', ' ') }}°C</strong>
                    </article>
                    <article>
                        <p class="ha-eyebrow">Humidité</p>
                        <strong class="text-2xl font-bold">{{ $weather['humidity'] }} %</strong>
                    </article>
                    <article>
                        <p class="ha-eyebrow">Vent</p>
                        <strong class="text-2xl font-bold">{{ number_format((float) $weather['wind_speed'], 1, ',', ' ') }} km/h</strong>
                    </article>
                </div>
                <div class="mt-4 space-y-2 text-sm">
                    <p><strong>Condition :</strong> {{ $weather['condition'] }}</p>
                    @if(!empty($weather['sunrise']))
                        <p><strong>Lever du soleil :</strong> {{ \Carbon\Carbon::createFromTimestamp($weather['sunrise'])->format('d/m/Y H:i') }}</p>
                    @endif
                    @if(!empty($weather['sunset']))
                        <p><strong>Coucher du soleil :</strong> {{ \Carbon\Carbon::createFromTimestamp($weather['sunset'])->format('d/m/Y H:i') }}</p>
                    @endif
                </div>
            @else
                <p class="ha-muted">Aucune donnée météo disponible pour ce gouvernorat via l’API.</p>
            @endif
        </section>
    @endif

    <section class="ha-admin-panel"><div class="pv-table-wrap"><table class="pv-table"><thead><tr><th>Date</th><th>Gouvernorat</th><th>Type</th><th>Température</th><th>Humidité</th><th>Vent</th><th>UV</th><th>Actions</th></tr></thead><tbody>
        @forelse($releves as $releve)
            <tr><td>{{ $releve->date_mesure->format('d/m/Y H:i') }}</td><td>{{ $releve->zone->nom }}</td><td>{{ \App\Models\ReleveMeteo::TYPES[$releve->type] }}</td><td>{{ $releve->temperature }} °C</td><td>{{ $releve->humidite }} %</td><td>{{ $releve->vitesse_vent ? $releve->vitesse_vent.' km/h' : '—' }}</td><td>{{ $releve->indice_uv ?? '—' }}</td><td class="pv-table-actions"><a href="{{ route('admin.canicule.releves.edit', $releve) }}">Modifier</a><form method="POST" action="{{ route('admin.canicule.releves.destroy', $releve) }}" onsubmit="return confirm('Supprimer ce relevé ?')">@csrf @method('DELETE')<button type="submit">Supprimer</button></form></td></tr>
        @empty<tr><td colspan="8">Aucun relevé météo enregistré.</td></tr>@endforelse
        </tbody></table></div>{{ $releves->links() }}</section>
</div>
@endsection