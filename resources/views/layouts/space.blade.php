<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ ['user' => 'Espace citoyen', 'agent-campagne' => 'Espace campagnes', 'admin' => 'Administration'][$space] }} — HeatAlert</title>
    <link rel="stylesheet" href="{{ asset('assets/tabler/css/tabler.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/heatalert/spaces.css') }}">
    <script src="{{ asset('assets/heatalert/spaces.js') }}" defer></script>
</head>
<body class="ha-space">
    <div class="ha-shell" data-space-shell>
        <button type="button" class="ha-icon-button ha-mobile-toggle" aria-label="Ouvrir le menu" aria-controls="space-sidebar" aria-expanded="false" data-mobile-open>
            <x-space-icon name="menu" />
        </button>
        <div class="ha-overlay" data-sidebar-overlay hidden></div>
        <x-space-sidebar :space="$space" />
        <main class="ha-main" aria-label="Zone principale">@yield('content')</main>
    </div>
</body>
</html>
