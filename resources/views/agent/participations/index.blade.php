@extends('layouts.space')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/heatalert/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/heatalert/agent-participations.css') }}">
@endpush

@section('content')
@php
    $campaignStatuses = ['brouillon' => 'Brouillon', 'publiee' => 'Publiée', 'en_cours' => 'En cours', 'terminee' => 'Terminée', 'annulee' => 'Annulée'];
    $participationStatuses = ['en_attente' => 'En attente', 'acceptee' => 'Acceptée', 'refusee' => 'Refusée', 'annulee' => 'Annulée'];
    $presenceLabels = ['non_renseignee' => 'Présence non renseignée', 'present' => 'Présent', 'absent' => 'Absent'];
@endphp
<div class="ha-dashboard ha-participations">
    <header class="ha-dashboard-header">
        <div>
            <p class="ha-dashboard-eyebrow">PARTICIPATIONS ET BÉNÉVOLAT</p>
            <h1>Participants et bénévoles</h1>
            <p>Vos participants regroupés par campagne, puis par mission. Chaque personne apparaît une seule fois dans sa campagne.</p>
        </div>
        <a class="ha-dashboard-action" href="{{ route('agent-campagne.campagnes') }}">Mes campagnes <span aria-hidden="true">→</span></a>
    </header>

    <p class="ha-participations-guide">Campagne <span aria-hidden="true">→</span> Participants <span aria-hidden="true">→</span> Missions rejointes · {{ $campagnes->total() }} campagne(s)</p>

    <div class="ha-campaign-trees">
        @forelse($campagnes as $campagne)
            @php
                $registrations = $campagne->missions->flatMap(fn ($mission) => $mission->participations);
                $participants = $registrations->groupBy('user_id')->sortBy(fn ($items) => mb_strtolower($items->first()->user?->name ?? ''));
                $missions = $campagne->missions->keyBy('id');
            @endphp
            <section class="ha-campaign-tree" aria-labelledby="campaign-{{ $campagne->id }}">
                <header class="ha-campaign-root">
                    <span class="ha-tree-icon" aria-hidden="true"><x-space-icon name="flag" /></span>
                    <div class="ha-campaign-root-copy">
                        <p>Campagne <span class="ha-participation-status">{{ $campaignStatuses[$campagne->statut] ?? $campagne->statut }}</span></p>
                        <h2 id="campaign-{{ $campagne->id }}">{{ $campagne->titre }}</h2>
                        <p>{{ $campagne->lieu }} · {{ $campagne->date_debut->format('d/m/Y') }} — {{ $campagne->date_fin->format('d/m/Y') }}</p>
                    </div>
                    <div class="ha-campaign-counts"><strong>{{ $participants->count() }} participant(s)</strong><span>{{ $registrations->count() }} inscription(s) · {{ $missions->count() }} mission(s)</span></div>
                </header>

                @if($participants->isEmpty())
                    <div class="ha-tree-empty">Aucun participant pour cette campagne pour le moment.</div>
                @else
                    <ul class="ha-participant-branches" aria-label="Participants de {{ $campagne->titre }}">
                        @foreach($participants as $items)
                            @php $participant = $items->first()->user; @endphp
                            <li class="ha-participant-branch">
                                <article class="ha-participant-node">
                                    <header class="ha-participant-heading">
                                        <span class="ha-participant-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($participant?->name ?? '?', 0, 2)) }}</span>
                                        <div><h3>{{ $participant?->name ?? 'Compte indisponible' }}</h3><p>{{ $participant?->email }}</p></div>
                                        <span class="ha-participant-total">{{ $items->count() }} mission(s)</span>
                                    </header>
                                    <ul class="ha-participant-missions" aria-label="Missions rejointes">
                                        @foreach($items as $participation)
                                            @php $mission = $missions->get($participation->mission_id); @endphp
                                            <li>
                                                <div class="ha-participant-mission-title"><x-space-icon name="briefcase" /><strong>{{ $mission->titre }}</strong></div>
                                                <p>{{ $mission->date_debut->format('d/m/Y à H:i') }} · {{ $mission->lieu }}</p>
                                                <div class="ha-participant-badges"><span class="ha-participation-status" data-status="{{ $participation->statut }}">{{ $participationStatuses[$participation->statut] ?? $participation->statut }}</span><span>{{ $presenceLabels[$participation->presence] ?? 'Présence non renseignée' }}</span></div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </article>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @empty
            <div class="ha-dashboard-empty"><span aria-hidden="true">♧</span><strong>Aucune campagne pour le moment</strong><p>Les participants apparaîtront ici lorsque des inscriptions seront enregistrées dans vos campagnes.</p></div>
        @endforelse
    </div>
    @if($campagnes->hasPages())
        <nav class="ha-participations-pagination" aria-label="Pagination des campagnes">
            @if($campagnes->previousPageUrl())<a href="{{ $campagnes->previousPageUrl() }}" rel="prev">← Précédent</a>@endif
            <span>Page {{ $campagnes->currentPage() }} sur {{ $campagnes->lastPage() }}</span>
            @if($campagnes->nextPageUrl())<a href="{{ $campagnes->nextPageUrl() }}" rel="next">Suivant →</a>@endif
        </nav>
    @endif
    <p class="ha-dashboard-footnote">Toutes les inscriptions sont affichées, y compris celles en attente, refusées ou annulées. Une inscription acceptée ne confirme pas la présence.</p>
</div>
@endsection
