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
      <header class="navbar navbar-expand-md d-print-none">
        <div class="container-xl">
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
            <span class="navbar-toggler-icon"></span>
          </button>
          <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
            <a href="{{ url('/') }}">HeatAlert</a>
          </h1>
        </div>
      </header>

      <div class="navbar-expand-md">
        <div class="collapse navbar-collapse" id="navbar-menu">
          <div class="navbar navbar-light">
            <div class="container-xl">
              <ul class="navbar-nav">
                <li class="nav-item active">
                  <a class="nav-link" href="{{ url('/') }}">
                    <span class="nav-link-title">Accueil</span>
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div class="page-wrapper">
        <div class="page-header d-print-none">
          <div class="container-xl">
            <div class="row g-2 align-items-center">
              <div class="col">
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
      </div>
    </div>

    <script src="{{ asset('assets/tabler/js/tabler.js') }}" defer></script>
    @stack('scripts')
  </body>
</html>
