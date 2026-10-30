@extends('layouts.space', ['space' => 'admin'])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page">
    <a class="ha-back-link" href="{{ route('admin.prevention.fiches.index') }}">← Retour aux fiches</a>
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Administration / Conseils de prévention</p>
            <h1>Ajouter une fiche</h1>
            <p class="ha-muted">Les champs avec * sont obligatoires.</p>
        </div>
    </header>

    @include('prevention.partials.messages')

    <section class="ha-admin-panel pv-box">
        <form class="pv-form" method="POST" action="{{ route('admin.prevention.fiches.store') }}" enctype="multipart/form-data" novalidate>
            @csrf
            @include('prevention.admin.fiches._form')
            <div class="pv-actions">
                <a class="ha-text-button" href="{{ route('admin.prevention.fiches.index') }}">Annuler</a>
                <button class="ha-primary-button" type="submit">Ajouter la fiche</button>
            </div>
        </form>
    </section>
</div>
@endsection
