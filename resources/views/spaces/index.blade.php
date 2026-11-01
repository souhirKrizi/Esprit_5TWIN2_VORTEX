@extends('layouts.space')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/heatalert/dashboard.css') }}">
@endpush

@section('content')
<div class="ha-dashboard">
    <header class="ha-dashboard-header">
        <div>
            <p class="ha-dashboard-eyebrow">VOTRE ACTIVITÉ EN UN COUP D’ŒIL</p>
            <h1>{{ $dashboard['title'] }}</h1>
            <p>{{ $dashboard['description'] }}</p>
        </div>
        <a class="ha-dashboard-action" href="{{ $dashboard['link'] }}">{{ $dashboard['action'] }} <span aria-hidden="true">→</span></a>
    </header>

    <section class="ha-dashboard-metrics" aria-label="Indicateurs clés">
        @foreach($dashboard['metrics'] as $label => $value)
            <article class="ha-dashboard-metric">
                <span>{{ $label }}</span>
                <strong>{{ number_format($value, 0, ',', ' ') }}</strong>
                <span class="ha-dashboard-metric-note">{{ $loop->first ? 'Vue d’ensemble' : 'Données actuelles' }}</span>
            </article>
        @endforeach
    </section>

    <section class="ha-dashboard-charts" aria-label="Graphiques statistiques">
        @foreach($dashboard['charts'] as $chart)
            <x-dashboard-chart :chart="$chart" :index="$loop->index" />
        @endforeach
    </section>
    <p class="ha-dashboard-footnote">Données actualisées à chaque ouverture du tableau de bord. Les inscriptions ne constituent pas une confirmation de présence.</p>
</div>
@endsection
