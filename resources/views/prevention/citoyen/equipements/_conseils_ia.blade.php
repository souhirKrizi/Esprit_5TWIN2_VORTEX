@php
    $conseils = $equipement->conseils_ia;
    $priorites = \App\Services\Prevention\ConseilsIaService::PRIORITES;
    $moments = ['avant' => 'Avant', 'pendant' => 'Pendant', 'apres' => 'Après'];
@endphp

<section class="pv-ia {{ $conseils ? 'pv-ia-prio-'.$conseils['priorite'] : '' }}" aria-labelledby="pv-ia-titre">
    <header class="pv-ia-tete">
        <span class="pv-ia-icone" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11 3l1.9 5.1L18 10l-5.1 1.9L11 17l-1.9-5.1L4 10l5.1-1.9L11 3z"/><path d="M18.5 14l.9 2.6 2.6.9-2.6.9-.9 2.6-.9-2.6-2.6-.9 2.6-.9.9-2.6z"/></svg>
        </span>
        <div class="pv-ia-intitule">
            <h2 id="pv-ia-titre">Conseils pour cet équipement</h2>
            <p>{{ $conseils ? 'Préparés par l’IA d’après vos informations' : 'L’IA prépare un plan adapté à votre situation' }}</p>
        </div>
        @if($conseils)
            <span class="pv-ia-priorite"><i></i>{{ $priorites[$conseils['priorite']] }}</span>
        @endif
    </header>

    <div data-ia-contenu>
        @if($conseils)
            <p class="pv-ia-raison">{{ $conseils['raison'] }}</p>

            <div class="pv-ia-etapes">
                @foreach($moments as $cle => $libelle)
                    @if(! empty($conseils[$cle]))
                        <div class="pv-ia-etape">
                            <h3><span>{{ $loop->iteration }}</span>{{ $libelle }}</h3>
                            <ul>
                                @foreach($conseils[$cle] as $conseil)
                                    <li>{{ $conseil }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            </div>

            @if(! empty($conseils['a_eviter']))
                <div class="pv-ia-eviter">
                    <h3>À éviter</h3>
                    <ul>
                        @foreach($conseils['a_eviter'] as $conseil)
                            <li>{{ $conseil }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @else
            <p class="pv-ia-texte">L’IA analyse ces informations pour vous dire quoi faire avant, pendant et après une canicule ou une coupure :</p>
            <ul class="pv-ia-analyse">
                <li>{{ $equipement->libelleType() }}</li>
                <li>Sensible à : {{ mb_strtolower($equipement->libelleSensibilite()) }}</li>
                <li>Autonomie : {{ $equipement->autonomie_heures === null ? 'non renseignée' : $equipement->autonomie_heures.' h' }}</li>
                <li class="{{ $equipement->description ? '' : 'is-manquant' }}">{{ $equipement->description ? 'Description du problème' : 'Sans description' }}</li>
            </ul>
            @unless($equipement->description)
                <p class="pv-ia-astuce">Pour des conseils plus précis, <a href="{{ route('prevention.equipements.edit', $equipement) }}">décrivez d’abord le problème</a>.</p>
            @endunless
        @endif
    </div>

    <div class="pv-ia-chargement" data-ia-chargement role="status" hidden>
        <span class="pv-ia-points" aria-hidden="true"><i></i><i></i><i></i></span>
        <span data-ia-message>L’IA étudie votre équipement…</span>
    </div>

    <footer class="pv-ia-pied">
        <form method="POST" action="{{ route('prevention.equipements.conseils', $equipement) }}" data-ia-formulaire>
            @csrf
            <button class="{{ $conseils ? 'ha-secondary-button' : 'ha-primary-button' }}" type="submit">{{ $conseils ? 'Générer de nouveaux conseils' : 'Générer mes conseils' }}</button>
        </form>
        @if($conseils)
            <small>Générés le {{ $equipement->conseils_ia_le?->format('d/m/Y à H:i') }}</small>
        @endif
    </footer>

    <p class="pv-ia-avis">
        Ces conseils sont générés automatiquement et peuvent contenir des erreurs.
        @if($equipement->type === 'appareil_medical')
            <strong>Ils ne remplacent pas l’avis de votre médecin. En cas de danger, appelez le SAMU au 190.</strong>
        @endif
    </p>
</section>
