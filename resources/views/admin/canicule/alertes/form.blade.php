@extends('layouts.space', ['space' => 'admin'])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page ha-canicule-admin">
    <a class="ha-back-link" href="{{ route('admin.canicule.alertes.index') }}">Retour aux alertes</a>
    <header class="ha-admin-heading"><div><p class="ha-eyebrow">Administration / Météo et canicule</p><h1>{{ $alerte->exists ? 'Modifier l’alerte' : 'Créer une alerte' }}</h1></div></header>
    @include('admin.canicule.partials.messages')
    <section class="ha-admin-panel pv-box"><form class="pv-form" method="POST" action="{{ $alerte->exists ? route('admin.canicule.alertes.update', $alerte) : route('admin.canicule.alertes.store') }}">
        @csrf @if($alerte->exists) @method('PUT') @endif
        <div class="pv-grid-2">
            <div class="pv-field"><label for="zone_id">Gouvernorat *</label><select id="zone_id" name="zone_id" required>@foreach($zones as $zone)<option value="{{ $zone->id }}" @selected((string) old('zone_id', $alerte->zone_id) === (string) $zone->id)>{{ $zone->nom }}</option>@endforeach</select>@error('zone_id')<p class="pv-erreur">{{ $message }}</p>@enderror</div>
            <div class="pv-field"><label for="titre">Titre *</label><input id="titre" name="titre" value="{{ old('titre', $alerte->titre) }}" maxlength="150" required>@error('titre')<p class="pv-erreur">{{ $message }}</p>@enderror</div>
            <div class="pv-field pv-field-wide"><label for="description">Description *</label><textarea id="description" name="description" rows="5" maxlength="5000" required>{{ old('description', $alerte->description) }}</textarea>@error('description')<p class="pv-erreur">{{ $message }}</p>@enderror</div>
            <div class="pv-field"><label for="niveau_gravite">Niveau de gravité *</label><select id="niveau_gravite" name="niveau_gravite">@foreach(\App\Models\Alerte::NIVEAUX as $value => $label)<option value="{{ $value }}" @selected(old('niveau_gravite', $alerte->niveau_gravite) === $value)>{{ $label }}</option>@endforeach</select>@error('niveau_gravite')<p class="pv-erreur">{{ $message }}</p>@enderror</div>
            <div class="pv-field"><label for="statut">Statut *</label><select id="statut" name="statut">@foreach(\App\Models\Alerte::STATUTS as $value => $label)<option value="{{ $value }}" @selected(old('statut', $alerte->statut) === $value)>{{ $label }}</option>@endforeach</select>@error('statut')<p class="pv-erreur">{{ $message }}</p>@enderror</div>
            <div class="pv-field"><label for="debut_validite">Début de validité *</label><input id="debut_validite" type="datetime-local" name="debut_validite" value="{{ old('debut_validite', $alerte->debut_validite?->format('Y-m-d\TH:i')) }}" required>@error('debut_validite')<p class="pv-erreur">{{ $message }}</p>@enderror</div>
            <div class="pv-field"><label for="fin_validite">Fin de validité *</label><input id="fin_validite" type="datetime-local" name="fin_validite" value="{{ old('fin_validite', $alerte->fin_validite?->format('Y-m-d\TH:i')) }}" required>@error('fin_validite')<p class="pv-erreur">{{ $message }}</p>@enderror</div>
        </div>
        <div class="pv-actions"><a class="ha-secondary-button" href="{{ route('admin.canicule.alertes.index') }}">Annuler</a><button class="ha-primary-button" type="submit">Enregistrer</button></div>
    </form></section>
</div>
@endsection