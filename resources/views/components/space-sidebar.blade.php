@props(['space'])
@php
    // The protected route selects the sidebar for the authenticated user's role.
    $spaces = [
        'user' => [
            'label' => 'Espace citoyen', 'name' => 'Amira Ben Ali', 'initials' => 'AB', 'role' => 'Citoyen',
            'sections' => [
                'Accueil' => [['Mon tableau de bord', 'grid']],
                'Météo et canicule' => [['Carte des alertes', 'pin'], ['Météo de ma zone', 'sun'], ['Alertes en cours', 'alert']],
                'Signalements et ressources' => [['Ressources utiles', 'book'], ['Incidents dans ma zone', 'pin'], ['Mes signalements', 'message'], ['Faire un signalement', 'plus']],
                'Conseils de prévention' => [['Conseils', 'file'], ['Mes équipements', 'tag'], ['Mes questions', 'message']],
                'Campagnes et bénévolat' => [['Découvrir les campagnes', 'flag'], ['Missions disponibles', 'briefcase'], ['Mes participations', 'check']],
                'Mon compte' => [['Mon profil', 'user'], ['Préférences de notification', 'bell']],
            ],
        ],
        'agent-campagne' => [
            'label' => 'Espace campagnes', 'name' => 'Nassef Fadhlaoui', 'initials' => 'YM', 'role' => 'Agent de campagne',
            'sections' => [
                'Accueil' => [['Tableau de bord campagnes', 'grid']],
                'Gestion des campagnes' => [['Mes campagnes', 'flag'], ['Créer une campagne', 'plus']],
                'Gestion des missions' => [['Mes missions', 'briefcase'], ['Créer une mission', 'plus']],
                'Participations' => [['Inscriptions à traiter', 'check'], ['Participants et bénévoles', 'users']],
                'Informations utiles' => [['Alertes en cours', 'alert'], ['Ressources et incidents', 'pin'], ['Conseils de prévention', 'book']],
                'Mon compte' => [['Mon profil', 'user'], ['Préférences de notification', 'bell']],
            ],
        ],
        'admin' => [
            'label' => 'Administration', 'name' => 'Sami Trabelsi', 'initials' => 'ST', 'role' => 'Administrateur',
            'sections' => [
                'Accueil' => [['Tableau de bord', 'grid']],
                'Utilisateurs' => [['Comptes utilisateurs', 'users'], ['Profils', 'user'], ['Préférences de notification', 'bell']],
                'Météo et canicule' => [['Carte des alertes', 'pin'], ['Relevés météo', 'sun'], ['Alertes', 'alert'], ['Zones géographiques', 'pin']],
                'Signalements citoyens' => [['Signalements', 'message'], ['Ressources utiles', 'book'], ['Incidents', 'alert']],
                'Conseils de prévention' => [['Fiches conseils', 'file'], ['Questions des habitants', 'message']],
                'Campagnes et bénévolat' => [['Toutes les campagnes', 'flag'], ['Missions', 'briefcase'], ['Participations', 'check']],
            ],
        ],
    ];
    $menu = $spaces[$space];
    if ($account = auth()->user()) {
        $menu['name'] = $account->name;
        $menu['initials'] = mb_strtoupper(mb_substr($account->name, 0, 2));
        $menu['role'] = ['user' => 'Citoyen', 'agent-campagne' => 'Agent de campagne', 'admin' => 'Administrateur'][$account->role] ?? 'Citoyen';
    }
    // Module « Conseils de prévention » : libellé => [route du lien, routes qui le rendent actif].
    $liensPrevention = [
        'Conseils' => ['prevention.fiches.index', ['prevention.fiches.*']],
        'Conseils de prévention' => ['prevention.fiches.index', ['prevention.fiches.*']],
        'Mes équipements' => ['prevention.equipements.index', ['prevention.equipements.*']],
        'Mes questions' => ['prevention.questions.index', ['prevention.questions.*']],
        'Fiches conseils' => ['admin.prevention.fiches.index', ['admin.prevention.fiches.*', 'admin.prevention.gestes.*']],
        'Questions des habitants' => ['admin.prevention.questions.index', ['admin.prevention.questions.*']],
    ];
    $liensCanicule = [
        'Relevés météo' => ['admin.canicule.releves.index', ['admin.canicule.releves.*']],
        'Alertes' => ['admin.canicule.alertes.index', ['admin.canicule.alertes.*']],
        'Zones géographiques' => ['admin.canicule.zones.index', ['admin.canicule.zones.*']],
    ];
    $liensCaniculeCitoyen = [
        'Carte des alertes' => ['user.canicule.carte', ['user.canicule.carte']],
        'Météo de ma zone' => ['user.canicule.meteo', ['user.canicule.meteo']],
        'Alertes en cours' => ['user.canicule.alertes', ['user.canicule.alertes']],
    ];
@endphp
<aside id="space-sidebar" class="ha-sidebar" aria-label="{{ $menu['label'] }}" tabindex="-1">
    <header class="ha-sidebar-header">
        <a class="ha-brand" href="{{ auth()->user()->spaceUrl() }}" aria-label="HeatAlert — {{ $menu['label'] }}">
            <img class="ha-brand-logo" src="{{ asset('assets/images/Logo_HeatAlert_avec_flamme_integree.png') }}" alt="HeatAlert">
            <span class="ha-brand-copy"><span>{{ $menu['label'] }}</span></span>
        </a>
        <button type="button" class="ha-icon-button ha-mobile-close" aria-label="Fermer le menu" data-mobile-close><x-space-icon name="close" /></button>
    </header>
    <button type="button" class="ha-collapse ha-icon-button" aria-label="Réduire la sidebar" aria-controls="space-sidebar" aria-expanded="true" data-desktop-collapse title="Réduire la sidebar"><x-space-icon name="panel" /></button>
    <nav class="ha-navigation" aria-label="Navigation {{ $menu['label'] }}">
        @foreach($menu['sections'] as $section => $items)
            @php $sectionIndex = $loop->index; @endphp
            <section class="ha-section">
                <button type="button" class="ha-section-toggle" aria-expanded="true" aria-controls="section-{{ $sectionIndex }}" data-section-toggle aria-label="{{ $section }}" title="{{ $section }}">
                    <span class="ha-section-label">{{ $section }}</span><x-space-icon name="chevron" />
                </button>
                <ul class="ha-menu" id="section-{{ $sectionIndex }}">
                    @foreach($items as [$label, $icon])
                        <li>
                            @if($space === 'admin' && $label === 'Comptes utilisateurs')
                                <a href="{{ route('admin.users.index') }}" class="ha-menu-item {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" data-menu-item {{ request()->routeIs('admin.users.*') ? 'aria-current=page' : '' }} aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></a>
                            @elseif($space === 'admin' && $label === 'Toutes les campagnes')
                                <a href="{{ route('admin.campagnes.index') }}" class="ha-menu-item {{ request()->routeIs('admin.campagnes.*') ? 'is-active' : '' }}" data-menu-item @if(request()->routeIs('admin.campagnes.*')) aria-current="page" @endif aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></a>
                            @elseif($space === 'admin' && $label === 'Missions')
                                <a href="{{ route('admin.missions.index') }}" class="ha-menu-item {{ request()->routeIs('admin.missions.*') ? 'is-active' : '' }}" data-menu-item @if(request()->routeIs('admin.missions.*')) aria-current="page" @endif aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></a>
                            @elseif($space === 'admin' && $label === 'Signalements')
                                <a href="{{ route('admin.signalements.index') }}" class="ha-menu-item {{ request()->routeIs('admin.signalements.index', 'admin.signalements.show', 'admin.signalements.validate', 'admin.signalements.reject') ? 'is-active' : '' }}" data-menu-item @if(request()->routeIs('admin.signalements.index', 'admin.signalements.show', 'admin.signalements.validate', 'admin.signalements.reject')) aria-current="page" @endif aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></a>
                            @elseif($space === 'admin' && $label === 'Ressources utiles')
                                <a href="{{ route('admin.signalements.carte') }}" class="ha-menu-item {{ request()->routeIs('admin.signalements.carte') && request('etat') !== 'rouge' ? 'is-active' : '' }}" data-menu-item @if(request()->routeIs('admin.signalements.carte') && request('etat') !== 'rouge') aria-current="page" @endif aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></a>
                            @elseif($space === 'admin' && $label === 'Incidents')
                                <a href="{{ route('admin.signalements.carte', ['etat' => 'rouge']) }}" class="ha-menu-item {{ request()->routeIs('admin.signalements.carte') && request('etat') === 'rouge' ? 'is-active' : '' }}" data-menu-item @if(request()->routeIs('admin.signalements.carte') && request('etat') === 'rouge') aria-current="page" @endif aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></a>
                            @elseif($space === 'admin' && $label === 'Carte des alertes')
                                <a href="{{ route('admin.canicule.carte') }}" class="ha-menu-item {{ request()->routeIs('admin.canicule.carte') ? 'is-active' : '' }}" data-menu-item @if(request()->routeIs('admin.canicule.carte')) aria-current="page" @endif aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></a>
                            @elseif($space === 'user' && isset($liensCaniculeCitoyen[$label]))
                                @php [$routeCitoyen, $routesCitoyen] = $liensCaniculeCitoyen[$label]; $lienCitoyenActif = request()->routeIs(...$routesCitoyen); @endphp
                                <a href="{{ route($routeCitoyen) }}" class="ha-menu-item {{ $lienCitoyenActif ? 'is-active' : '' }}" data-menu-item @if($lienCitoyenActif) aria-current="page" @endif aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></a>
                            @elseif(isset($liensCanicule[$label]))
                                @php [$routeCanicule, $routesCanicule] = $liensCanicule[$label]; $lienCaniculeActif = request()->routeIs(...$routesCanicule); @endphp
                                <a href="{{ route($routeCanicule) }}" class="ha-menu-item {{ $lienCaniculeActif ? 'is-active' : '' }}" data-menu-item @if($lienCaniculeActif) aria-current="page" @endif aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></a>
                            @elseif($space === 'user' && $label === 'Ressources utiles')
                                <a href="{{ route('user.signalements.carte') }}" class="ha-menu-item {{ request()->routeIs('user.signalements.carte', 'user.ressources.show') ? 'is-active' : '' }}" data-menu-item @if(request()->routeIs('user.signalements.carte', 'user.ressources.show')) aria-current="page" @endif aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></a>
                            @elseif($space === 'user' && $label === 'Incidents dans ma zone')
                                <a href="{{ route('user.signalements.public.index') }}" class="ha-menu-item {{ request()->routeIs('user.signalements.public.*') ? 'is-active' : '' }}" data-menu-item @if(request()->routeIs('user.signalements.public.*')) aria-current="page" @endif aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></a>
                            @elseif($space === 'user' && $label === 'Mes signalements')
                                <a href="{{ route('user.signalements.index') }}" class="ha-menu-item {{ request()->routeIs('user.signalements.index', 'user.signalements.show') ? 'is-active' : '' }}" data-menu-item @if(request()->routeIs('user.signalements.index', 'user.signalements.show')) aria-current="page" @endif aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></a>
                            @elseif($space === 'user' && $label === 'Faire un signalement')
                                <a href="{{ route('user.signalements.create') }}" class="ha-menu-item {{ request()->routeIs('user.signalements.create', 'user.signalements.edit') ? 'is-active' : '' }}" data-menu-item @if(request()->routeIs('user.signalements.create', 'user.signalements.edit')) aria-current="page" @endif aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></a>
                            @elseif($space === 'agent-campagne' && in_array($label, ['Mes campagnes', 'Mes missions']))
                                @php $agentRoute = $label === 'Mes campagnes' ? 'agent-campagne.campagnes' : 'agent-campagne.missions'; @endphp
                                <a href="{{ route($agentRoute) }}" class="ha-menu-item {{ request()->routeIs($agentRoute) ? 'is-active' : '' }}" data-menu-item @if(request()->routeIs($agentRoute)) aria-current="page" @endif aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></a>
                            @elseif($space === 'user' && in_array($label, ['Découvrir les campagnes', 'Missions disponibles']))
                                <a href="{{ route('user.campagnes') }}" class="ha-menu-item {{ $label === 'Découvrir les campagnes' && request()->routeIs('user.campagnes') ? 'is-active' : '' }}" data-menu-item @if($label === 'Découvrir les campagnes' && request()->routeIs('user.campagnes')) aria-current="page" @endif aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></a>
                            @elseif($space === 'user' && $label === 'Mes participations')
                                <a href="{{ route('user.participations') }}" class="ha-menu-item {{ request()->routeIs('user.participations') ? 'is-active' : '' }}" data-menu-item @if(request()->routeIs('user.participations')) aria-current="page" @endif aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></a>
                            @elseif(isset($liensPrevention[$label]))
                                @php [$routePrevention, $routesActives] = $liensPrevention[$label]; $lienActif = request()->routeIs(...$routesActives); @endphp
                                <a href="{{ route($routePrevention) }}" class="ha-menu-item {{ $lienActif ? 'is-active' : '' }}" data-menu-item @if($lienActif) aria-current="page" @endif aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></a>
                            @elseif($sectionIndex === 0 && $loop->first)
                                <a href="{{ auth()->user()->spaceUrl() }}" class="ha-menu-item {{ request()->routeIs('spaces.*') ? 'is-active' : '' }}" data-menu-item @if(request()->routeIs('spaces.*')) aria-current="page" @endif aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></a>
                            @else
                                <button type="button" class="ha-menu-item" data-menu-item aria-pressed="false" aria-label="{{ $label }}" title="{{ $label }}"><x-space-icon :name="$icon" /><span class="ha-item-label">{{ $label }}</span></button>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </nav>
    <footer class="ha-sidebar-footer">
        <div class="ha-profile" title="{{ $menu['name'] }} — {{ $menu['role'] }}">
            <span class="ha-avatar" aria-hidden="true">{{ $menu['initials'] }}</span>
            <div class="ha-profile-copy"><strong>{{ $menu['name'] }}</strong><span class="ha-role">{{ $menu['role'] }}</span></div>
        </div>
        <a href="{{ route('home') }}" class="ha-site-link" aria-label="Voir le site" title="Voir le site"><x-space-icon name="external" /><span class="ha-item-label">Voir le site</span></a>
        @auth
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="ha-menu-item ha-logout" aria-label="Déconnexion" title="Déconnexion"><x-space-icon name="external" /><span class="ha-item-label">Déconnexion</span></button>
            </form>
        @endauth
    </footer>
</aside>
