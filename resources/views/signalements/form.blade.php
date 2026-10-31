@extends('layouts.space', ['space' => $space])

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
<link rel="stylesheet" href="{{ asset('assets/heatalert/signalements.css') }}">
@endpush
@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script src="{{ asset('assets/heatalert/signalements.js') }}" defer></script>
@endpush

@section('content')
@php($editing = $signalement->exists)
<div class="ha-admin-page ha-signalements-page" data-signalement-form data-editing="{{ $editing ? '1' : '0' }}">
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Signalements citoyens</p>
            <h1>{{ $editing ? 'Modifier mon signalement' : 'Nouveau signalement' }}</h1>
            <p class="ha-muted">Aidez les autres citoyens à trouver une ressource disponible ou à identifier un incident.</p>
        </div>
        <a class="ha-text-button" href="{{ route('user.signalements.index') }}">Mes signalements</a>
    </header>

    @include('signalements.partials.form-errors')

    <form class="ha-signalement-form" method="POST" action="{{ $editing ? route('user.signalements.update', $signalement) : route('user.signalements.store') }}" enctype="multipart/form-data" novalidate data-signalement-submit>
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="ha-form-main">
            <section class="ha-admin-panel ha-form-panel">
                <div class="ha-panel-heading"><div><p class="ha-eyebrow">Étape 1</p><h2>Où se trouve le signalement ?</h2></div></div>
                <div id="signalement-form-map" class="ha-form-map" data-lat="{{ old('latitude', $signalement->latitude ?? 33.8) }}" data-lng="{{ old('longitude', $signalement->longitude ?? 9.5) }}"></div>
                <div class="ha-form-map-actions"><button class="ha-secondary-button" type="button" data-geolocate>Ma position</button><span class="ha-form-help">Déplacez le marqueur sur la carte pour choisir une position.</span></div>
                <input type="hidden" name="latitude" value="{{ old('latitude', $signalement->latitude ?? 33.8) }}" data-coordinate="latitude" class="@error('latitude') is-invalid @enderror">
                @error('latitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <input type="hidden" name="longitude" value="{{ old('longitude', $signalement->longitude ?? 9.5) }}" data-coordinate="longitude" class="@error('longitude') is-invalid @enderror">
                @error('longitude')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <p class="ha-field-error" data-error-for="position"></p>
            </section>

            <section class="ha-admin-panel ha-form-panel">
                <div class="ha-panel-heading"><div><p class="ha-eyebrow">Étape 2</p><h2>Que souhaitez-vous signaler ?</h2></div></div>
                <label class="ha-choice"><input type="radio" name="type_signalement" value="incident" class="@error('type_signalement') is-invalid @enderror" @checked(old('type_signalement', $signalement->type_signalement) === 'incident') data-signalement-type><span><strong>Un incident</strong><small>Une ressource est indisponible ou rencontre un problème.</small></span></label>
                <label class="ha-choice"><input type="radio" name="type_signalement" value="ressource_ok" class="@error('type_signalement') is-invalid @enderror" @checked(old('type_signalement', $signalement->type_signalement) === 'ressource_ok') data-signalement-type><span><strong>Tout fonctionne</strong><small>Confirmez qu’une ressource est accessible et opérationnelle.</small></span></label>
                @error('type_signalement')<div class="invalid-feedback">{{ $message }}</div>@enderror

                <div class="ha-form-grid">
                    <label>Ressource concernée
                        <select name="ressource_utile_id" class="@error('ressource_utile_id') is-invalid @enderror">
                            <option value="">Aucune ressource précise</option>
                            @foreach($ressources as $ressource)
                                <option value="{{ $ressource->id }}" @selected((string) old('ressource_utile_id', $signalement->ressource_utile_id) === (string) $ressource->id)>{{ $ressource->nom }} — {{ $ressource->gouvernorat }}</option>
                            @endforeach
                        </select>
                        @error('ressource_utile_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </label>
                    <label data-incident-field>Type d’incident
                        <select name="type_incident" data-incident-input class="@error('type_incident') is-invalid @enderror">
                            <option value="">Choisir</option>
                            <option value="coupure_electricite" @selected(old('type_incident', $signalement->type_incident) === 'coupure_electricite')>Coupure d’électricité</option>
                            <option value="point_eau_hors_service" @selected(old('type_incident', $signalement->type_incident) === 'point_eau_hors_service')>Point d’eau hors service</option>
                            <option value="centre_ferme" @selected(old('type_incident', $signalement->type_incident) === 'centre_ferme')>Centre fermé</option>
                            <option value="autre" @selected(old('type_incident', $signalement->type_incident) === 'autre')>Autre</option>
                        </select>
                        @error('type_incident')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <span class="ha-field-error" data-error-for="incident"></span>
                    </label>
                    <label class="ha-field-wide">Commentaire <span class="ha-character-count"><span data-comment-count>0</span>/1000</span>
                        <textarea name="commentaire" rows="5" minlength="10" maxlength="1000" data-commentaire class="@error('commentaire') is-invalid @enderror">{{ old('commentaire', $signalement->commentaire) }}</textarea>
                        @error('commentaire')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <span class="ha-field-error" data-error-for="commentaire"></span>
                    </label>

                    @if(config('assistant_ia.enabled') && filled(config('assistant_ia.key')))
                        <div class="ha-field-wide ha-assistant-container" data-assistant-container data-url="{{ route('user.signalements.assistant-redaction') }}">
                            <div class="ha-assistant-actions">
                                <button type="button" class="ha-secondary-button ha-assistant-trigger" data-assistant-trigger>
                                    <span data-assistant-btn-label>Améliorer ma description avec l'IA</span>
                                    <span data-assistant-btn-spinner hidden>Amélioration en cours…</span>
                                </button>
                            </div>
                            <div class="ha-assistant-error" data-assistant-error hidden role="alert"></div>
                            <div class="ha-assistant-suggestion" data-assistant-suggestion hidden>
                                <div class="ha-assistant-suggestion-title">
                                    <strong>Suggestion de l'assistant</strong>
                                </div>
                                <p class="ha-assistant-suggestion-text" data-assistant-text></p>
                                <div class="ha-assistant-suggestion-actions">
                                    <button type="button" class="ha-primary-button" data-assistant-use>Utiliser cette version</button>
                                    <button type="button" class="ha-secondary-button" data-assistant-ignore>Ignorer</button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            <section class="ha-admin-panel ha-form-panel">
                <div class="ha-panel-heading"><div><p class="ha-eyebrow">Étape 3</p><h2>Ajouter des photos</h2></div><span class="ha-form-help">5 images maximum, 5 Mo chacune.</span></div>
                <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple data-images data-existing-count="{{ $editing ? $signalement->piecesJointes->count() : 0 }}" class="@error('images') is-invalid @enderror">
                @error('images')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="ha-image-preview-grid" data-image-previews></div>
                @if($editing) @include('signalements.partials.piece-jointes', ['signalement' => $signalement]) @endif
                <p class="ha-field-error" data-error-for="images"></p>
            </section>
        </div>
        <footer class="ha-form-actions"><a class="ha-secondary-button" href="{{ $editing ? route('user.signalements.show', $signalement) : route('user.signalements.carte') }}">Annuler</a><button class="ha-primary-button" type="submit" data-submit-button>{{ $editing ? 'Enregistrer les modifications' : 'Envoyer le signalement' }}</button></footer>
    </form>
</div>
@endsection
