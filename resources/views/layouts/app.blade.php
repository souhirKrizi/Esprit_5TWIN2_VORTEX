<!doctype html>
<html lang="fr">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    <title>@yield('title', 'HeatAlert')</title>
    <link rel="stylesheet" href="{{ asset('assets/tabler/css/tabler.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/tabler/css/tabler-vendors.min.css') }}" />
    @stack('styles')
  </head>
  <body>
    <script src="{{ asset('assets/tabler/js/tabler-theme.js') }}"></script>

    <div class="page">
      <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu">
            <span class="navbar-toggler-icon"></span>
          </button>
          <h1 class="navbar-brand navbar-brand-autodark">
            <a href="{{ url('/') }}">HeatAlert</a>
          </h1>
          <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
              <li class="nav-item {{ request()->is('/') ? 'active' : '' }}">
                <a class="nav-link" href="{{ url('/') }}">
                  <span class="nav-link-title">Tableau de bord</span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#">
                  <span class="nav-link-title">Alertes</span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#">
                  <span class="nav-link-title">Zones surveillées</span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#">
                  <span class="nav-link-title">Utilisateurs</span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="#">
                  <span class="nav-link-title">Paramètres</span>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </aside>

      <header class="navbar navbar-expand-md navbar-light d-print-none">
        <div class="container-xl">
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="navbar-nav flex-row order-md-last">
            <div class="nav-item dropdown">
              <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                <span class="avatar avatar-sm bg-primary-lt">SK</span>
                <div class="d-none d-xl-block ps-2">
                  <div>{{ auth()->user()->name ?? 'Souhir' }}</div>
                  <div class="mt-1 small text-secondary">Administrateur</div>
                </div>
              </a>
              <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                <a href="#" class="dropdown-item">Profil</a>
                <a href="#" class="dropdown-item">Déconnexion</a>
              </div>
            </div>
          </div>
        </div>
      </header>

      <div class="page-wrapper">
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
                <div class="page-pretitle">HeatAlert</div>
                <h2 class="page-title">@yield('page-title', 'Tableau de bord')</h2>
              </div>
            </div>
          </div>
        </div>

        <div class="page-body">
          <div class="container-xl">
            @yield('content')
          </div>
        </div>

        <footer class="footer footer-transparent d-print-none">
          <div class="container-xl">
            <div class="row text-center align-items-center flex-row-reverse">
              <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                <ul class="list-inline list-inline-dots mb-0">
                  <li class="list-inline-item">HeatAlert &copy; {{ date('Y') }}</li>
                </ul>
              </div>
            </div>
          </div>
        </footer>
      </div>
    </div>

    <script src="{{ asset('assets/tabler/js/tabler.js') }}" defer></script>
    @stack('scripts')
  </body>
</html>
