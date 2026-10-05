@extends('layouts.auth')

@section('title', 'Mot de passe oublié')

@section('editorial')
<div class="inline-flex items-center gap-space-xs self-start px-space-md py-space-xs rounded-full bg-secondary-container/15 text-secondary">
  <span class="material-symbols-outlined text-[18px]">lock_reset</span>
  <span class="font-label-md text-label-md font-bold tracking-wide uppercase">Récupération de compte</span>
</div>
<div><h1 class="font-headline-lg text-headline-lg text-primary-container">Pas de panique, <span class="text-secondary-container">on vous aide</span>.</h1>
<p class="mt-space-sm text-body-lg text-on-surface-variant max-w-xl">Indiquez votre adresse e-mail : nous vous envoyons un code à 6 chiffres pour choisir un nouveau mot de passe.</p></div>
@endsection

@section('form')
<div class="flex flex-col gap-space-xs mb-space-lg">
  <h2 class="font-headline-lg text-headline-lg text-primary-container">Mot de passe oublié ?</h2>
  <p class="font-body-md text-body-md text-on-surface-variant">Saisissez l'adresse e-mail associée à votre compte HeatAlert.</p>
</div>

@if ($errors->any())
<div role="alert" class="mb-space-md p-space-md rounded-lg bg-error-container/60 text-on-error-container font-body-sm text-body-sm flex items-start gap-space-sm">
  <span class="material-symbols-outlined text-[20px] text-error">error</span>
  <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<form action="{{ route('password.email') }}" method="POST" class="flex flex-col gap-space-md">
@csrf
<div class="flex flex-col gap-space-xs">
  <label for="email" class="font-label-md text-label-md text-primary-container font-semibold">Adresse e-mail</label>
  <div class="relative flex items-center">
    <span class="material-symbols-outlined auth-field-icon text-[20px]">mail</span>
    <input class="auth-input" id="email" name="email" type="email" maxlength="255" value="{{ old('email') }}" placeholder="exemple@domaine.tn" autocomplete="email" autofocus required>
  </div>
</div>

<button type="submit" class="auth-submit w-full h-12 rounded-xl shadow-md transition-colors duration-200">Recevoir le code</button>

<p class="text-center text-body-sm text-on-surface-variant">
  <a class="inline-flex items-center gap-1 text-secondary-container font-bold hover:underline" href="{{ route('signin') }}">
    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
    Retour à la connexion
  </a>
</p>
</form>
@endsection
