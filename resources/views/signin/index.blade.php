@extends('layouts.auth')

@section('title', 'Connexion')

@section('editorial')
<div class="inline-flex items-center gap-space-xs self-start px-space-md py-space-xs rounded-full bg-secondary-container/15 text-secondary">
  <span class="material-symbols-outlined text-[18px]">local_fire_department</span>
  <span class="font-label-md text-label-md font-bold tracking-wide uppercase">Vigilance canicule en Tunisie</span>
</div>
<div><h1 class="font-headline-lg text-headline-lg text-primary-container">Anticipez la chaleur, <span class="text-secondary-container">protégez-vous</span>.</h1>
<p class="mt-space-sm text-body-lg text-on-surface-variant max-w-xl">Retrouvez vos préférences et suivez les alertes de chaleur dans les régions qui vous concernent.</p></div>
@endsection

@section('form')
@if (session('status'))
<p role="status" class="mb-space-md text-secondary">{{ session('status') }}</p>
@endif
<div class="flex flex-col gap-space-xs mb-space-lg">
<h2 class="font-headline-lg text-headline-lg text-primary-container">
              Heureux de vous retrouver
            </h2>
<p class="font-body-md text-body-md text-on-surface-variant">
              Connectez-vous à votre compte civique HeatAlert.
            </p>
</div>

<form action="{{ route('login') }}" class="flex flex-col gap-space-md" id="signInForm" method="POST">
@csrf
@if ($errors->any())
<div role="alert" class="text-error text-body-sm">
  <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif
<!-- Champ Adresse e-mail -->
<div class="flex flex-col gap-space-xs">
<label class="font-label-md text-label-md text-primary-container font-semibold flex items-center justify-between" for="email">
<span>Adresse e-mail</span>
<span class="hidden text-error font-label-sm text-label-sm font-bold" id="emailStatusBadge">Invalide</span>
</label>
<div class="relative flex items-center">
<span class="material-symbols-outlined absolute left-space-md text-[20px] text-on-surface-variant pointer-events-none transition-colors duration-200" id="emailIcon">
                  mail
                </span>
<input autocomplete="email" class="w-full h-11 pl-11 pr-space-md rounded-lg bg-surface-container-low text-primary-container placeholder:text-on-surface-variant/60 font-body-md text-body-md transition-all duration-200 focus:outline-none focus:bg-surface-container-lowest focus:shadow-sm" id="email" name="email" value="{{ old('email') }}" placeholder="exemple@domaine.tn" required type="email"/>
</div>
</div>
<!-- Champ Mot de passe -->
<div class="flex flex-col gap-space-xs">
<label class="font-label-md text-label-md text-primary-container font-semibold flex items-center justify-between" for="password">
<span>Mot de passe</span>
<span class="hidden text-error font-label-sm text-label-sm font-bold" id="passwordStatusBadge">Requis</span>
</label>
<div class="relative flex items-center">
<span class="material-symbols-outlined absolute left-space-md text-[20px] text-on-surface-variant pointer-events-none transition-colors duration-200" id="passwordIcon">
                  lock
                </span>
<input autocomplete="current-password" class="w-full h-11 pl-11 pr-11 rounded-lg bg-surface-container-low text-primary-container placeholder:text-on-surface-variant/60 font-body-md text-body-md transition-all duration-200 focus:outline-none focus:bg-surface-container-lowest focus:shadow-sm" id="password" name="password" placeholder="••••••••••••" required type="password"/>
<button aria-label="Afficher ou masquer le mot de passe" class="absolute right-space-md text-on-surface-variant hover:text-primary-container p-1 rounded transition-colors" id="togglePasswordBtn" data-toggle-password="password" type="button">
<span class="material-symbols-outlined text-[20px]" id="eyeIcon">visibility</span>
</button>
</div>
</div>
<!-- Options: Se souvenir de moi & Oubli mot de passe -->
<div class="flex items-center justify-between pt-space-xs">
<label class="flex items-center gap-space-sm cursor-pointer select-none">
<input class="w-4 h-4 rounded text-secondary-container focus:ring-0 focus:ring-offset-0 bg-surface-container-low cursor-pointer accent-secondary-container" id="remember" name="remember" type="checkbox"/>
<span class="font-body-sm text-body-sm text-on-surface-variant">Se souvenir de moi</span>
</label>
<button type="button" disabled title="Disponible prochainement" class="font-label-md text-label-md text-on-surface-variant opacity-70">Mot de passe oublié ?</button>
</div>
<!-- Bouton Principal de Soumission -->
<div class="pt-space-xs">
<button class="w-full h-12 flex items-center justify-center gap-space-xs rounded-xl auth-submit font-label-lg text-label-lg shadow-md transition-all duration-200 active:scale-[0.99]" id="submitButton" type="submit">
<!-- Spinner pour micro-interaction (masqué par défaut) -->
<svg class="hidden animate-spin h-5 w-5 text-on-primary" fill="none" id="buttonSpinner" viewbox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
<path class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" fill="currentColor"></path>
</svg>
<span id="buttonText">Connexion</span>
</button>
</div>
<!-- Inscription alternative -->
<div class="text-center pt-space-xs">
<p class="font-body-sm text-body-sm text-on-surface-variant">
                Vous n’avez pas de compte ? 
                <a class="text-secondary-container font-bold hover:underline ml-1" href="{{ route('signup') }}">
                  Créer un compte
                </a>
</p>
</div>
</form>
@endsection
