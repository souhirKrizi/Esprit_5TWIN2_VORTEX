@extends('layouts.space', ['space' => 'admin'])

@section('content')
<div class="ha-admin-page ha-detail-page">
    <a class="ha-back-link" href="{{ route('admin.users.index') }}">← Retour aux comptes</a>
    @if(session('success'))<div class="ha-notice ha-notice-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="ha-notice ha-notice-error">{{ session('error') }}</div>@endif
    <header class="ha-admin-heading"><div><p class="ha-eyebrow">Fiche utilisateur</p><h1>{{ $user->name }}</h1><p class="ha-muted">{{ $user->email }}</p></div><span class="ha-status {{ $user->archived ? 'is-archived' : 'is-active' }}"><i></i>{{ $user->archived ? 'Compte archivé' : 'Compte actif' }}</span></header>
    <div class="ha-detail-grid">
        <section class="ha-admin-panel"><h2>Informations du compte</h2><dl class="ha-details-list"><div><dt>Nom complet</dt><dd>{{ $user->name }}</dd></div><div><dt>Adresse e-mail</dt><dd>{{ $user->email }}</dd></div><div><dt>Gouvernorat</dt><dd>{{ $user->governorate ?: 'Non renseigné' }}</dd></div><div><dt>Alertes e-mail</dt><dd>{{ $user->email_alerts ? 'Activées' : 'Désactivées' }}</dd></div><div><dt>Compte créé le</dt><dd>{{ $user->created_at?->format('d/m/Y à H:i') }}</dd></div></dl></section>
        <section class="ha-admin-panel"><h2>Accès et actions</h2><form class="ha-detail-form" method="POST" action="{{ route('admin.users.role', $user) }}">@csrf @method('PATCH')<label>Rôle<select name="role"><option value="user" @selected($user->role === 'user')>Citoyen</option><option value="agent-campagne" @selected($user->role === 'agent-campagne')>Agent campagne</option><option value="admin" @selected($user->role === 'admin')>Administrateur</option></select></label><button class="ha-primary-button" type="submit">Enregistrer le rôle</button></form><div class="ha-action-divider"></div><a class="ha-secondary-button" href="{{ route('admin.users.edit', $user) }}">Modifier les informations</a><form method="POST" action="{{ route('admin.users.archive', $user) }}">@csrf @method('PATCH')<button class="ha-danger-button" type="submit">{{ $user->archived ? 'Restaurer le compte' : 'Mettre en archive' }}</button></form></section>
    </div>
</div>
@endsection
