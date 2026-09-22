@extends('layouts.app')

@section('title', 'HeatAlert')
@section('page-title', 'Bienvenue')

@section('content')
  <div class="row row-deck row-cards">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <h3 class="card-title">Template Tabler intégré</h3>
          <p class="text-secondary">
            Le projet Laravel {{ app()->version() }} utilise maintenant le template Tabler.
          </p>
        </div>
      </div>
    </div>
  </div>
@endsection
