@extends('layouts.auth')

@section('title', 'Réinitialiser le mot de passe')

@section('editorial')
<div class="inline-flex items-center gap-space-xs self-start px-space-md py-space-xs rounded-full bg-secondary-container/15 text-secondary">
  <span class="material-symbols-outlined text-[18px]">password</span>
  <span class="font-label-md text-label-md font-bold tracking-wide uppercase">Nouveau mot de passe</span>
</div>
<div><h1 class="font-headline-lg text-headline-lg text-primary-container">Choisissez un <span class="text-secondary-container">mot de passe sûr</span>.</h1>
<p class="mt-space-sm text-body-lg text-on-surface-variant max-w-xl">Saisissez le code reçu par e-mail puis votre nouveau mot de passe (8 caractères minimum, dont une majuscule).</p></div>
@endsection

@section('form')
<div class="flex flex-col gap-space-xs mb-space-lg">
  <h2 class="font-headline-lg text-headline-lg text-primary-container">Réinitialiser le mot de passe</h2>
  <p class="font-body-md text-body-md text-on-surface-variant">Entrez le code à 6 chiffres reçu par e-mail.</p>
</div>

@if (session('status'))
<div role="status" class="mb-space-md p-space-md rounded-lg bg-secondary-container/10 text-on-secondary-container font-body-sm text-body-sm flex items-start gap-space-sm">
  <span class="material-symbols-outlined text-[20px] text-secondary">mark_email_read</span>
  <span>{{ session('status') }}</span>
</div>
@endif

@if ($errors->any())
<div role="alert" class="mb-space-md p-space-md rounded-lg bg-error-container/60 text-on-error-container font-body-sm text-body-sm flex items-start gap-space-sm">
  <span class="material-symbols-outlined text-[20px] text-error">error</span>
  <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<form action="{{ route('password.update') }}" method="POST" class="flex flex-col gap-space-md" id="resetCodeForm">
@csrf
<div class="flex flex-col gap-space-xs">
  <label for="email" class="font-label-md text-label-md text-primary-container font-semibold">Adresse e-mail</label>
  <div class="relative flex items-center">
    <span class="material-symbols-outlined auth-field-icon text-[20px]">mail</span>
    <input class="auth-input" id="email" name="email" type="email" maxlength="255" value="{{ old('email', $email) }}" placeholder="exemple@domaine.tn" autocomplete="email" required>
  </div>
</div>

<div class="flex flex-col gap-space-xs">
  <label for="code" class="font-label-md text-label-md text-primary-container font-semibold">Code de vérification</label>
  <div class="relative flex items-center">
    <span class="material-symbols-outlined auth-field-icon text-[20px]">pin</span>
    <input class="auth-input tracking-[0.5em] font-bold" id="code" name="code" type="text" inputmode="numeric" pattern="\d{6}" minlength="6" maxlength="6" placeholder="••••••" autocomplete="one-time-code" title="Le code doit contenir exactement 6 chiffres." @if($email) autofocus @endif required>
  </div>
</div>

<div class="flex flex-col gap-space-xs">
  <label for="password" class="font-label-md text-label-md text-primary-container font-semibold">Nouveau mot de passe</label>
  <div class="relative flex items-center">
    <span class="material-symbols-outlined auth-field-icon text-[20px]">lock</span>
    <input class="auth-input" id="password" name="password" type="password" minlength="8" placeholder="Votre nouveau mot de passe" autocomplete="new-password" required>
    <button type="button" class="auth-eye" data-toggle-password="password" aria-label="Afficher le mot de passe" aria-pressed="false"><span class="material-symbols-outlined text-[20px]">visibility</span></button>
  </div>
</div>

<div class="flex flex-col gap-space-xs">
  <label for="password-confirmation" class="font-label-md text-label-md text-primary-container font-semibold">Confirmer le mot de passe</label>
  <div class="relative flex items-center">
    <span class="material-symbols-outlined auth-field-icon text-[20px]">lock</span>
    <input class="auth-input" id="password-confirmation" name="password_confirmation" type="password" minlength="8" placeholder="Confirmez votre nouveau mot de passe" autocomplete="new-password" required>
    <button type="button" class="auth-eye" data-toggle-password="password-confirmation" aria-label="Afficher le mot de passe" aria-pressed="false"><span class="material-symbols-outlined text-[20px]">visibility</span></button>
  </div>
</div>

<ul class="flex flex-col gap-space-xs -mt-space-sm text-body-sm" id="passwordRules" aria-live="polite">
  <li class="flex items-center gap-space-xs text-on-surface-variant" data-rule="length"><span class="material-symbols-outlined text-[18px]">radio_button_unchecked</span>Au moins 8 caractères</li>
  <li class="flex items-center gap-space-xs text-on-surface-variant" data-rule="uppercase"><span class="material-symbols-outlined text-[18px]">radio_button_unchecked</span>Au moins une lettre majuscule</li>
  <li class="flex items-center gap-space-xs text-on-surface-variant" data-rule="match"><span class="material-symbols-outlined text-[18px]">radio_button_unchecked</span>Les deux mots de passe correspondent</li>
</ul>

<button type="submit" class="auth-submit w-full h-12 rounded-xl shadow-md transition-colors duration-200">Réinitialiser le mot de passe</button>

<p class="text-center text-body-sm text-on-surface-variant">
  Vous n'avez rien reçu ?
  <a class="text-secondary-container font-bold hover:underline ml-1" href="{{ route('password.request') }}">Renvoyer un code</a>
</p>
<p class="text-center text-body-sm text-on-surface-variant">
  <a class="inline-flex items-center gap-1 text-secondary-container font-bold hover:underline" href="{{ route('signin') }}">
    <span class="material-symbols-outlined text-[18px]">arrow_back</span>
    Retour à la connexion
  </a>
</p>
</form>

<script>
  (() => {
    const code = document.getElementById('code');
    const password = document.getElementById('password');
    const confirmation = document.getElementById('password-confirmation');
    const rules = document.getElementById('passwordRules');

    code.addEventListener('input', () => {
      code.value = code.value.replace(/\D/g, '').slice(0, 6);
      code.setCustomValidity(code.value.length === 6 ? '' : 'Le code doit contenir exactement 6 chiffres.');
    });

    const setRule = (name, ok, touched) => {
      const item = rules.querySelector(`[data-rule="${name}"]`);
      item.classList.toggle('text-green-700', ok);
      item.classList.toggle('text-error', touched && !ok);
      item.classList.toggle('text-on-surface-variant', !touched && !ok);
      item.querySelector('span').textContent = ok ? 'check_circle' : (touched ? 'cancel' : 'radio_button_unchecked');
    };

    const checkPasswords = () => {
      const value = password.value;
      const longEnough = value.length >= 8;
      const hasUppercase = /[A-Z]/.test(value);
      const matches = confirmation.value.length > 0 && confirmation.value === value;

      setRule('length', longEnough, value.length > 0);
      setRule('uppercase', hasUppercase, value.length > 0);
      setRule('match', matches, confirmation.value.length > 0);

      password.setCustomValidity(
        !longEnough ? 'Le mot de passe doit contenir au moins 8 caractères.'
        : !hasUppercase ? 'Le mot de passe doit contenir au moins une lettre majuscule.'
        : ''
      );
      confirmation.setCustomValidity(matches ? '' : 'Les deux mots de passe ne correspondent pas.');
    };

    password.addEventListener('input', checkPasswords);
    confirmation.addEventListener('input', checkPasswords);
  })();
</script>
@endsection
