@extends('layouts.app')

@section('title', 'HeatAlert')
@section('page-title', 'Tableau de bord')

@section('content')
  <div class="row row-deck row-cards">
    <div class="col-sm-6 col-lg-3">
      <div class="card card-sm">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col-auto">
              <span class="bg-red text-white avatar">32°C</span>
            </div>
            <div class="col">
              <div class="font-weight-medium">Température max</div>
              <div class="text-secondary">Zone Nord</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card card-sm">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col-auto">
              <span class="bg-orange text-white avatar">5</span>
            </div>
            <div class="col">
              <div class="font-weight-medium">Alertes actives</div>
              <div class="text-secondary">Dernières 24h</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card card-sm">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col-auto">
              <span class="bg-green text-white avatar">12</span>
            </div>
            <div class="col">
              <div class="font-weight-medium">Zones surveillées</div>
              <div class="text-secondary">Toutes régions</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card card-sm">
        <div class="card-body">
          <div class="row align-items-center">
            <div class="col-auto">
              <span class="bg-blue text-white avatar">148</span>
            </div>
            <div class="col">
              <div class="font-weight-medium">Utilisateurs</div>
              <div class="text-secondary">Inscrits</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Dernières alertes</h3>
        </div>
        <div class="table-responsive">
          <table class="table card-table table-vcenter text-nowrap datatable">
            <thead>
              <tr>
                <th>Zone</th>
                <th>Température</th>
                <th>Niveau</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>Tunis - Centre</td>
                <td>38°C</td>
                <td><span class="badge bg-red text-white">Critique</span></td>
                <td>22/09/2026 14:30</td>
              </tr>
              <tr>
                <td>Sfax - Sud</td>
                <td>34°C</td>
                <td><span class="badge bg-orange text-white">Élevé</span></td>
                <td>22/09/2026 12:10</td>
              </tr>
              <tr>
                <td>Sousse - Littoral</td>
                <td>31°C</td>
                <td><span class="badge bg-yellow text-white">Modéré</span></td>
                <td>22/09/2026 09:45</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
@endsection
