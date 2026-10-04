@extends('layouts.space', ['space' => 'admin'])

@section('content')
<div class="ha-admin-page ha-detail-page">
    <a class="ha-back-link" href="{{ route('admin.users.show', $user) }}">← Retour à la fiche</a>
    <header class="ha-admin-heading"><div><p class="ha-eyebrow">Administration / Utilisateurs</p><h1>Modifier le compte</h1><p class="ha-muted">Mettez à jour les informations de {{ $user->name }}.</p></div></header>
    @if($errors->any())<div class="ha-notice ha-notice-error">Veuillez corriger les champs indiqués.</div>@endif
    <section class="ha-admin-panel ha-form-panel"><form class="ha-user-edit-form" method="POST" action="{{ route('admin.users.update', $user) }}">@csrf @method('PUT')<div class="ha-form-grid"><label>Nom complet<input name="name" value="{{ old('name', $user->name) }}" required>@error('name')<small>{{ $message }}</small>@enderror</label><label>Adresse e-mail<input type="email" name="email" value="{{ old('email', $user->email) }}" required>@error('email')<small>{{ $message }}</small>@enderror</label><label>Gouvernorat<input name="governorate" value="{{ old('governorate', $user->governorate) }}">@error('governorate')<small>{{ $message }}</small>@enderror</label><label>Rôle<select name="role"><option value="user" @selected(old('role', $user->role) === 'user')>Citoyen</option><option value="agent-campagne" @selected(old('role', $user->role) === 'agent-campagne')>Agent campagne</option><option value="admin" @selected(old('role', $user->role) === 'admin')>Administrateur</option></select>@error('role')<small>{{ $message }}</small>@enderror</label></div><div class="ha-form-actions"><a class="ha-text-button" href="{{ route('admin.users.show', $user) }}">Annuler</a><button class="ha-primary-button" type="submit">Enregistrer les modifications</button></div></form></section>
</div>
@endsection
