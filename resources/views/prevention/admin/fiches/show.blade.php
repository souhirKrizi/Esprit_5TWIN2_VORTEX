@extends('layouts.space', ['space' => 'admin'])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page">
    <a class="ha-back-link" href="{{ route('admin.prevention.fiches.index') }}">← Retour aux fiches</a>
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">{{ $fiche->libelleThematique() }}</p>
            <h1>{{ $fiche->titre }}</h1>
            <div class="pv-tags" style="margin-top:12px">
                <span class="pv-badge pv-statut-{{ $fiche->statut }}">{{ $fiche->libelleStatut() }}</span>
                <span class="pv-badge pv-niveau-{{ $fiche->niveau_alerte }}">Alerte {{ strtolower($fiche->libelleNiveau()) }}</span>
                @if($fiche->type_equipement)<span class="pv-badge">{{ $fiche->libelleEquipement() }}</span>@endif
            </div>
        </div>
        <div class="pv-heading-actions">
            <a class="ha-secondary-button" href="{{ route('prevention.fiches.show', $fiche) }}">Voir comme un habitant</a>
            <a class="ha-secondary-button" href="{{ route('admin.prevention.fiches.edit', $fiche) }}">Modifier</a>
            <form class="pv-inline-form" method="POST" action="{{ route('admin.prevention.fiches.destroy', $fiche) }}" data-confirm="Supprimer cette fiche et ses gestes ?">
                @csrf @method('DELETE')
                <button class="ha-danger-button" type="submit">Supprimer</button>
            </form>
        </div>
    </header>

    @include('prevention.partials.messages')

    <section class="ha-admin-panel pv-box pv-text">
        <h2>Contenu</h2>
        <p><strong>{{ $fiche->resume }}</strong></p>
        @foreach(preg_split('/\R{2,}/', trim($fiche->contenu)) as $paragraphe)
            <p>{!! nl2br(e($paragraphe)) !!}</p>
        @endforeach
    </section>

    <section class="ha-admin-panel pv-box">
        <h2>Gestes ({{ $fiche->gestes->count() }})</h2>
        <div class="ha-table-wrap">
            <table class="ha-users-table" style="min-width:600px">
                <thead><tr><th>Ordre</th><th>Geste</th><th>Moment</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse($fiche->gestes->sortBy(fn ($geste) => array_search($geste->moment, array_keys(\App\Models\GesteCle::MOMENTS)) * 100 + $geste->ordre) as $geste)
                        <tr>
                            <td>{{ $geste->ordre }}</td>
                            <td>{{ $geste->intitule }}</td>
                            <td>{{ $geste->libelleMoment() }}</td>
                            <td>
                                <div class="ha-row-actions">
                                    <a href="{{ route('admin.prevention.gestes.edit', $geste) }}">Modifier</a>
                                    <form class="pv-inline-form" method="POST" action="{{ route('admin.prevention.gestes.destroy', $geste) }}" data-confirm="Supprimer ce geste ?">
                                        @csrf @method('DELETE')
                                        <button class="pv-link pv-link-danger" type="submit">Supprimer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="ha-empty-state">Aucun geste pour cette fiche.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="ha-admin-panel pv-box">
        <h2>Ajouter un geste</h2>
        <form class="pv-form" method="POST" action="{{ route('admin.prevention.gestes.store', $fiche) }}" novalidate>
            @csrf
            @include('prevention.admin.gestes._form', ['geste' => $nouveauGeste])
            <div class="pv-actions"><button class="ha-primary-button" type="submit">Ajouter le geste</button></div>
        </form>
    </section>
</div>
@endsection
