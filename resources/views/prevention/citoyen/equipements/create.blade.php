@extends('layouts.space', ['space' => $espace])

@section('content')
@include('prevention.partials.assets')
<div class="ha-admin-page">
    <a class="ha-back-link" href="{{ route('prevention.equipements.index') }}">← Retour à mes équipements</a>
    <header class="ha-admin-heading">
        <div>
            <p class="ha-eyebrow">Mes équipements</p>
            <h1>Ajouter un équipement</h1>
            <p class="ha-muted">Les champs avec * sont obligatoires.</p>
        </div>
    </header>

    @include('prevention.partials.messages')

    <section class="ha-admin-panel pv-box">
        <form class="pv-form" method="POST" action="{{ route('prevention.equipements.store') }}" novalidate>
            @csrf
            @include('prevention.citoyen.equipements._form')
            <div class="pv-actions">
                <a class="ha-text-button" href="{{ route('prevention.equipements.index') }}">Annuler</a>
                <button class="ha-primary-button" type="submit">Ajouter</button>
            </div>
        </form>
    </section>
</div>
@endsection
