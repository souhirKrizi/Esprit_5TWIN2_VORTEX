@props(['chart', 'index'])
@php
    $colors = ['#ec6817', '#397baf', '#25866b', '#8a5bb5', '#c34f68'];
    $total = array_sum(array_column($chart['values'], 'value'));
    $max = max(1, ...array_column($chart['values'], 'value'));
    $offset = 0;
    $segments = [];
    foreach ($chart['values'] as $i => $item) {
        $end = $offset + ($total ? $item['value'] / $total * 100 : 0);
        $segments[] = $colors[$i % count($colors)].' '.$offset.'% '.$end.'%';
        $offset = $end;
    }
@endphp
<article class="ha-dashboard-chart {{ $chart['type'] === 'bar' ? 'ha-dashboard-chart-wide' : '' }}" aria-labelledby="chart-title-{{ $index }}">
    <header>
        <h2 id="chart-title-{{ $index }}">{{ $chart['title'] }}</h2>
        <p>{{ $chart['description'] }}</p>
    </header>
    @if($total === 0)
        <div class="ha-dashboard-empty"><span aria-hidden="true">▥</span><strong>Aucune donnée pour le moment</strong><p>Les statistiques apparaîtront ici dès les premières activités.</p></div>
    @endif
    @if($chart['type'] === 'donut')
        <div class="ha-dashboard-distribution">
            <div class="ha-dashboard-donut" aria-hidden="true" style="background: {{ $total ? 'conic-gradient('.implode(', ', $segments).')' : '#ececf4' }}">
                <div><strong>{{ $total }}</strong><span>Total</span></div>
            </div>
            <ul class="ha-dashboard-legend" aria-label="Valeurs du graphique">
                @foreach($chart['values'] as $item)
                    <li><span class="ha-dashboard-dot" style="background: {{ $colors[$loop->index % count($colors)] }}" aria-hidden="true"></span><span>{{ $item['label'] }}</span><strong>{{ $item['value'] }}</strong><small>{{ $total ? round($item['value'] / $total * 100) : 0 }} %</small></li>
                @endforeach
            </ul>
        </div>
    @else
        <div class="ha-dashboard-bars" role="list" aria-label="Valeurs mensuelles">
            @foreach($chart['values'] as $item)
                <div class="ha-dashboard-bar-column" role="listitem" aria-label="{{ $item['label'] }} : {{ $item['value'] }}">
                    <div class="ha-dashboard-bar-track" aria-hidden="true"><div class="ha-dashboard-bar" style="height: {{ $item['value'] / $max * 100 }}%"><strong>{{ $item['value'] }}</strong></div></div>
                    <span aria-hidden="true">{{ $item['label'] }}</span>
                </div>
            @endforeach
        </div>
    @endif
</article>
