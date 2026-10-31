<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rapport de campagne — {{ $donnees['campagne']['titre'] }} — HeatAlert</title>
    <style>
@page { margin: 36px 42px 56px; }
* { box-sizing: border-box; }
body { margin: 0; color: #18253d; font-family: 'DejaVu Sans', sans-serif; font-size: 10px; line-height: 1.6; }
.brand { border-bottom: 2px solid #f97316; padding-bottom: 20px; margin-bottom: 24px; }
.brand img { width: 90px; height: auto; vertical-align: middle; }
.brand > div { display: inline-block; vertical-align: middle; margin-left: 14px; }
.brand strong { font-size: 25px; }.brand p { margin: 0; color: #697387; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; }
.reference { float: right; text-align: right; color: #697387; font-size: 10px; padding-top: 13px; }
.cover { padding: 12px 0 24px; }h1 { font-size: 34px; line-height: 1.15; margin: 16px 0 22px; }
.cover h2 { font-size: 20px; color: #db6210; }.subtitle { color: #697387; }.metadata { background: #f4f6fa; border-left: 4px solid #f97316; padding: 12px 18px; margin-top: 24px; }.metadata p { margin: 4px 0; }
.eyebrow { color: #c2570b; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.5px; margin: 0 0 8px; page-break-after: avoid; }
.kpis { width: 100%; border-collapse: collapse; margin: 0 0 24px; table-layout: fixed; }.kpis td { padding: 15px 8px; background: #fff1e7; text-align: center; border-right: 4px solid white; }.kpis strong { display: block; font-size: 23px; color: #bc4e00; }.kpis span { display: block; font-size: 10px; }
.note { font-size: 10px; color: #5d687c; border-left: 3px solid #a7bad9; background: #f4f7fc; padding: 12px 16px; }
.section { margin-top: 32px; padding-top: 24px; border-top: 1px solid #dfe5ef; }.section h2 { font-size: 18px; line-height: 1.35; margin: 0 0 18px; page-break-after: avoid; }.section h3 { font-size: 12px; margin: 18px 0 8px; page-break-after: avoid; }
.prose { overflow-wrap: anywhere; orphans: 3; widows: 3; }.mission { padding: 0 0 18px; border-bottom: 1px solid #e6ebf2; }.mission-meta { color: #697387; font-size: 11px; }.analysis { border-left: 3px solid #f97316; padding-left: 14px; }
.data-table { width: 100%; border-collapse: collapse; font-size: 10px; margin: 16px 0; table-layout: fixed; }.data-table th { background: #19283f; color: white; padding: 8px 3px; }.data-table td { text-align: center; padding: 8px; background: #f2f5fa; }
.risk,.action { border-left: 3px solid #efb181; padding: 4px 16px; margin: 18px 0; }.priority { color: #ac4e09; font-size: 10px; text-transform: uppercase; }.action p { margin: 6px 0; }.report-footer { margin-top: 40px; padding-top: 20px; border-top: 2px solid #f97316; color: #697387; font-size: 10px; }
</style>
</head>
<body>
@php
    $fiche = $donnees['campagne'];
    $stats = $donnees['indicateurs'];
    $labels = ['brouillon' => 'Brouillon', 'publiee' => 'Publiée', 'en_cours' => 'En cours', 'terminee' => 'Terminée', 'annulee' => 'Annulée', 'ouverte' => 'Ouverte', 'complete' => 'Complète'];
    $missionAnalyses = collect($analyse['missions'] ?? [])->keyBy('id');
@endphp
<main class="report">
    <header class="brand">
        <img src="{{ $logo }}" alt="Logo HeatAlert">
        <div><strong>HeatAlert</strong><p>Météorologie civique · Tunisie</p></div>
        <span class="reference">CAMPAGNE #{{ $fiche['id'] }}<br>RAPPORT D’ANALYSE</span>
    </header>
    <section class="cover">
        <p class="eyebrow">Pilotage &amp; prévention de la chaleur</p>
        <h1>Rapport détaillé<br>de campagne</h1>
        <h2>{{ $fiche['titre'] }}</h2>
        <p class="subtitle">Analyse opérationnelle · Mobilisation citoyenne · Plan d’action</p>
        <div class="metadata">
            <p><strong>Lieu :</strong> {{ $fiche['lieu'] }} &nbsp; | &nbsp; <strong>Statut :</strong> {{ $labels[$fiche['statut']] ?? $fiche['statut'] }}</p>
            <p><strong>Période :</strong> {{ \Illuminate\Support\Carbon::parse($fiche['date_debut'])->format('d/m/Y') }} au {{ \Illuminate\Support\Carbon::parse($fiche['date_fin'])->format('d/m/Y') }}</p>
            <p><strong>Édition :</strong> {{ $genereLe->format('d/m/Y H:i') }} · Heure de Tunis</p>
        </div>
    </section>
    <table class="kpis"><tr>
        <td><strong>{{ $stats['missions'] }}</strong><span>Missions planifiées</span></td>
        <td><strong>{{ $stats['missions_terminees'] }}</strong><span>Missions terminées</span></td>
        <td><strong>{{ $stats['inscriptions_actives'] }}</strong><span>Inscriptions actives</span></td>
        <td><strong>{{ $stats['presents'] }}</strong><span>Présences confirmées</span></td>
    </tr></table>
    <aside class="note">Source : données enregistrées dans HeatAlert. Les inscriptions sont comptées par mission et ne représentent pas des personnes uniques. Les présences correspondent aux inscriptions acceptées marquées « présent ». L’analyse assistée par IA doit être relue par le responsable de campagne avant diffusion.</aside>
    <section class="section">
        <p class="eyebrow">Fiche de référence</p><h2>La campagne en bref</h2>
        <h3>Description</h3><p class="prose">{{ $fiche['description'] }}</p>
        <h3>Objectif déclaré</h3><p class="prose">{{ $fiche['objectif'] }}</p>
    </section>
    @foreach($sections as $key => $title)
        @if($key === 'limites')
            <section class="section">
                <p class="eyebrow">Analyse des missions</p><h2>Détail opérationnel, mission par mission</h2>
                @forelse($donnees['missions'] as $mission)
                <article class="mission">
                    <h3>{{ $loop->iteration }}. {{ $mission['titre'] }}</h3>
                    <p class="mission-meta">{{ $mission['lieu'] }} · {{ $labels[$mission['statut']] ?? $mission['statut'] }}<br>Du {{ \Illuminate\Support\Carbon::parse($mission['date_debut'])->format('d/m/Y H:i') }} au {{ \Illuminate\Support\Carbon::parse($mission['date_fin'])->format('d/m/Y H:i') }}</p>
                    <p class="prose">{{ $mission['description'] }}</p>
                    <table class="data-table"><thead><tr><th>Places</th><th>Actives</th><th>Acceptées</th><th>En attente</th><th>Présents</th><th>Absents</th></tr></thead><tbody><tr><td>{{ $mission['places'] }}</td><td>{{ $mission['inscriptions_actives'] }}</td><td>{{ $mission['acceptees'] }}</td><td>{{ $mission['en_attente'] }}</td><td>{{ $mission['presents'] }}</td><td>{{ $mission['absents'] }}</td></tr></tbody></table>
                    <div class="analysis">@foreach(preg_split('/\R+/u', $missionAnalyses[$mission['id']]['analyse'], -1, PREG_SPLIT_NO_EMPTY) as $paragraphe)<p class="prose">{{ $paragraphe }}</p>@endforeach</div>
                </article>
                @empty<p>Aucune mission enregistrée à la date de préparation du rapport.</p>@endforelse
            </section>
            <section class="section">
                <p class="eyebrow">Vigilance opérationnelle</p><h2>Risques et mesures préventives</h2>
                @foreach($analyse['risques'] as $risk)
                    <article class="risk"><span class="priority">Priorité {{ $risk['priorite'] }}</span><h3>{{ $risk['constat'] }}</h3><p class="prose">{{ $risk['mesure'] }}</p></article>
                @endforeach
            </section>
            <section class="section">
                <p class="eyebrow">Amélioration continue</p><h2>Recommandations et plan d’action proposé</h2>
                @foreach($analyse['recommandations'] as $action)
                    <article class="action"><h3>{{ $loop->iteration }}. {{ $action['action'] }}</h3><p><strong>Responsable proposé :</strong> {{ $action['responsable'] }}</p><p><strong>Délai proposé :</strong> {{ $action['delai'] }}</p><p><strong>Indicateur de suivi :</strong> {{ $action['indicateur'] }}</p></article>
                @endforeach
            </section>
        @endif
        <section class="section">
            <p class="eyebrow">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }} / Analyse de campagne</p>
            <h2>{{ $title }}</h2>
            @foreach(preg_split('/\R+/u', $analyse[$key], -1, PREG_SPLIT_NO_EMPTY) as $paragraphe)<p class="prose">{{ $paragraphe }}</p>@endforeach
        </section>
    @endforeach
    <footer class="report-footer"><strong>HeatAlert · Prévenir, mobiliser, protéger.</strong><br>Analyse avec {{ $modele }} - HeatAlert. Aucun résultat non enregistré ne constitue un fait vérifié.</footer>
</main>
</body>
</html>
