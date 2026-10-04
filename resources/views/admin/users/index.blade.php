@extends('layouts.space', ['space' => 'admin'])

@section('content')
<div class="ha-admin-page">
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Administration / Utilisateurs</p>
            <h1>Comptes utilisateurs</h1>
            <p class="ha-muted">Gérez les accès, les rôles et l’état des comptes HeatAlert.</p>
        </div>
        <div class="ha-heading-stat"><strong>{{ $users->total() }}</strong><span>comptes enregistrés</span></div>
    </header>

    @if(session('success'))
        <div class="ha-notice ha-notice-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="ha-notice ha-notice-error">{{ session('error') }}</div>
    @endif

    <section class="ha-admin-panel">
        <form class="ha-user-filters" method="GET" action="{{ route('admin.users.index') }}">
            <label class="ha-search-field">
                <span class="sr-only">Rechercher</span>
                <x-space-icon name="user" />
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Nom ou adresse e-mail">
            </label>
            <label class="ha-select-field">
                <span class="sr-only">Statut</span>
                <select name="status">
                    <option value="all">Tous les statuts</option>
                    <option value="active" @selected(request('status') === 'active')>Actifs</option>
                    <option value="archived" @selected(request('status') === 'archived')>Archivés</option>
                </select>
            </label>
            <button class="ha-primary-button" type="submit">Filtrer</button>
            @if(request()->hasAny(['search', 'status']))
                <a class="ha-text-button" href="{{ route('admin.users.index') }}">Réinitialiser</a>
            @endif
        </form>

        <div class="ha-table-wrap">
            <table class="ha-users-table">
                <thead><tr><th>Utilisateur</th><th>Rôle</th><th>Gouvernorat</th><th>Inscription</th><th>Statut</th><th><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                @forelse($users as $user)
                    <tr>
                        <td><div class="ha-user-cell"><span class="ha-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 2)) }}</span><span><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></span></div></td>
                        <td><span class="ha-role-badge role-{{ $user->role }}">{{ ['user' => 'Citoyen', 'agent-campagne' => 'Agent campagne', 'admin' => 'Administrateur'][$user->role] ?? $user->role }}</span></td>
                        <td>{{ $user->governorate ?: '—' }}</td>
                        <td>{{ $user->created_at?->format('d/m/Y') }}</td>
                        <td><span class="ha-status {{ $user->archived ? 'is-archived' : 'is-active' }}"><i></i>{{ $user->archived ? 'Archivé' : 'Actif' }}</span></td>
                        <td><div class="ha-row-actions"><a href="{{ route('admin.users.show', $user) }}" title="Voir les détails">Voir</a><a href="{{ route('admin.users.edit', $user) }}" title="Modifier l’utilisateur">Modifier</a></div></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="ha-empty-state">Aucun utilisateur ne correspond à ces critères.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())<div class="ha-pagination">{{ $users->links() }}</div>@endif
    </section>
</div>
@endsection
