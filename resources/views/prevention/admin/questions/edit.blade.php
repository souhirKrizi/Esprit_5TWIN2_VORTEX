@extends('layouts.space', ['space' => 'admin'])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page">
    <a class="ha-back-link" href="{{ route('admin.prevention.questions.index') }}">← Retour aux questions</a>
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Administration / Conseils de prévention</p>
            <h1>Répondre à la question</h1>
            <p class="ha-muted">Conseil : {{ $question->fiche->titre }}</p>
        </div>
    </header>

    @include('prevention.partials.messages')

    <section class="ha-admin-panel pv-box">
        <div class="pv-qr" style="margin-bottom:18px">
            <strong>{{ $question->question }}</strong>
            <small>Posée par {{ $question->auteur->name }} le {{ $question->created_at->format('d/m/Y') }}</small>
        </div>

        <form class="pv-form" method="POST" action="{{ route('admin.prevention.questions.update', $question) }}" novalidate>
            @csrf @method('PUT')
            <div class="pv-field">
                <label for="reponse">Réponse <span>*</span></label>
                <textarea id="reponse" name="reponse" rows="6" maxlength="1000" class="@error('reponse') is-invalid @enderror">{{ old('reponse', $question->reponse) }}</textarea>
                @error('reponse')<p class="pv-erreur">{{ $message }}</p>@enderror
                <small>Entre 10 et 1000 caractères.</small>
            </div>
            <div class="pv-actions">
                <a class="ha-text-button" href="{{ route('admin.prevention.questions.index') }}">Annuler</a>
                <button class="ha-primary-button" type="submit">Enregistrer la réponse</button>
            </div>
        </form>
    </section>
</div>
@endsection
