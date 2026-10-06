@extends('layouts.space', ['space' => $espace])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page">
    <a class="ha-back-link" href="{{ route('prevention.questions.index') }}">← Retour à mes questions</a>
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Mes questions</p>
            <h1>Modifier ma question</h1>
            <p class="ha-muted">Conseil : {{ $question->fiche->titre }}</p>
        </div>
    </header>

    @include('prevention.partials.messages')

    <section class="ha-admin-panel pv-box">
        <form class="pv-form" method="POST" action="{{ route('prevention.questions.update', $question) }}" novalidate>
            @csrf @method('PUT')
            <div class="pv-field">
                <label for="question">Votre question <span>*</span></label>
                <textarea id="question" name="question" rows="4" maxlength="500" class="@error('question') is-invalid @enderror">{{ old('question', $question->question) }}</textarea>
                @error('question')<p class="pv-erreur">{{ $message }}</p>@enderror
                <small>Entre 10 et 500 caractères.</small>
            </div>
            <div class="pv-actions">
                <a class="ha-text-button" href="{{ route('prevention.questions.index') }}">Annuler</a>
                <button class="ha-primary-button" type="submit">Enregistrer</button>
            </div>
        </form>
    </section>
</div>
@endsection
