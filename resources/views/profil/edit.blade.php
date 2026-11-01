@extends('layouts.space', ['space' => $compte->role])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page">
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Mon compte</p>
            <h1>Mon profil</h1>
            <p class="ha-muted">Les champs avec * sont obligatoires.</p>
        </div>
    </header>

    @include('prevention.partials.messages')

    <section class="ha-admin-panel pv-box">
        <h2>Mes informations</h2>
        <form class="pv-form" method="POST" action="{{ route($compte->role.'.profil.update') }}" enctype="multipart/form-data" novalidate>
            @csrf @method('PUT')

            <div class="pv-photo-ligne">
                <x-user-avatar :compte="$compte" class="pv-photo" />
                <div class="pv-field">
                    <label for="photo">Photo de profil</label>
                    <input id="photo" name="photo" type="file" accept=".jpg,.jpeg,.png,.webp" class="@error('photo') is-invalid @enderror">
                    @error('photo')<p class="pv-erreur">{{ $message }}</p>@enderror
                    <small>Facultatif. JPG, PNG ou WEBP, 2 Mo maximum.</small>
                    @if($compte->photo)
                        <label class="pv-case"><input type="checkbox" name="supprimer_photo" value="1" @checked(old('supprimer_photo'))> Supprimer ma photo</label>
                    @endif
                </div>
            </div>

            <div class="pv-grid-2">
                <div class="pv-field pv-field-wide">
                    <label for="name">Nom complet <span>*</span></label>
                    <input id="name" name="name" value="{{ old('name', $compte->name) }}" maxlength="255" autocomplete="name" class="@error('name') is-invalid @enderror">
                    @error('name')<p class="pv-erreur">{{ $message }}</p>@enderror
                </div>

                <div class="pv-field">
                    <label for="email">Adresse e-mail <span>*</span></label>
                    <input id="email" name="email" type="email" value="{{ old('email', $compte->email) }}" maxlength="255" autocomplete="email" class="@error('email') is-invalid @enderror">
                    @error('email')<p class="pv-erreur">{{ $message }}</p>@enderror
                </div>

                <div class="pv-field">
                    <label for="governorate">Gouvernorat <span>*</span></label>
                    <select id="governorate" name="governorate" class="@error('governorate') is-invalid @enderror">
                        <option value="">Choisir…</option>
                        @foreach(\App\Models\User::GOUVERNORATS as $gouvernorat)
                            <option value="{{ $gouvernorat }}" @selected(old('governorate', $compte->governorate) === $gouvernorat)>{{ $gouvernorat }}</option>
                        @endforeach
                    </select>
                    @error('governorate')<p class="pv-erreur">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="pv-actions">
                <button class="ha-primary-button" type="submit">Enregistrer</button>
            </div>
        </form>
    </section>

    <section class="ha-admin-panel pv-box" id="mot-de-passe">
        <h2>Changer mon mot de passe</h2>
        <form class="pv-form" method="POST" action="{{ route($compte->role.'.profil.password') }}" novalidate>
            @csrf @method('PUT')

            <div class="pv-grid-2">
                <div class="pv-field pv-field-wide">
                    <label for="current_password">Mot de passe actuel <span>*</span></label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="@error('current_password', 'motDePasse') is-invalid @enderror">
                    @error('current_password', 'motDePasse')<p class="pv-erreur">{{ $message }}</p>@enderror
                </div>

                <div class="pv-field">
                    <label for="password">Nouveau mot de passe <span>*</span></label>
                    <input id="password" name="password" type="password" autocomplete="new-password" class="@error('password', 'motDePasse') is-invalid @enderror">
                    @error('password', 'motDePasse')<p class="pv-erreur">{{ $message }}</p>@enderror
                    <small>Au moins 8 caractères, une majuscule et un chiffre.</small>
                </div>

                <div class="pv-field">
                    <label for="password_confirmation">Confirmer le nouveau mot de passe <span>*</span></label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                </div>
            </div>

            <div class="pv-actions">
                <button class="ha-primary-button" type="submit">Changer le mot de passe</button>
            </div>
        </form>
    </section>

    @unless($compte->role === 'admin')
    <section class="ha-admin-panel pv-box pv-box-danger" id="suppression">
        <h2>Supprimer mon compte</h2>
        <p class="pv-info">Votre session sera fermée et vous ne pourrez plus vous connecter. Votre compte sera archivé : vos données sont conservées et l’administrateur peut le restaurer à votre demande.</p>
        <form class="pv-form" method="POST" action="{{ route($compte->role.'.profil.destroy') }}" data-confirm="Voulez-vous vraiment supprimer votre compte ?" novalidate>
            @csrf @method('DELETE')

            <div class="pv-grid-2">
                <div class="pv-field">
                    <label for="mot_de_passe">Confirmez avec votre mot de passe <span>*</span></label>
                    <input id="mot_de_passe" name="mot_de_passe" type="password" autocomplete="current-password" class="@error('mot_de_passe', 'archivage') is-invalid @enderror">
                    @error('mot_de_passe', 'archivage')<p class="pv-erreur">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="pv-actions">
                <button class="ha-danger-button" type="submit">Supprimer mon compte</button>
            </div>
        </form>
    </section>
    @endunless
</div>
@endsection
