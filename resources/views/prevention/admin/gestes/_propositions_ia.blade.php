@php
    $propositions = collect($gestesProposes);
    $selectionEnvoyee = old('gestes') !== null;
    $etapes = ['L’IA lit le contenu de la fiche…', 'Recherche des actions concrètes…', 'Classement avant, pendant et après…'];
@endphp

<section class="pv-ia" id="gestes-ia" aria-labelledby="gestes-ia-titre">
    <header class="pv-ia-tete">
        <span class="pv-ia-icone" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M11 3l1.9 5.1L18 10l-5.1 1.9L11 17l-1.9-5.1L4 10l5.1-1.9L11 3z"/><path d="M18.5 14l.9 2.6 2.6.9-2.6.9-.9 2.6-.9-2.6-2.6-.9 2.6-.9.9-2.6z"/></svg>
        </span>
        <div class="pv-ia-intitule">
            <h2 id="gestes-ia-titre">Gestes proposés par l’IA</h2>
            <p>{{ $propositions->isEmpty() ? 'L’IA lit la fiche et prépare une liste de gestes' : 'Cochez les gestes à garder et corrigez-les si besoin' }}</p>
        </div>
    </header>

    <div data-ia-contenu>
        @if($propositions->isEmpty())
            <p class="pv-ia-texte">L’IA propose des gestes à faire avant, pendant et après l’épisode, d’après le contenu de cette fiche. Vous choisissez ensuite ceux à garder : rien n’est ajouté sans votre accord.</p>
        @else
            @error('gestes')<p class="pv-erreur pv-ia-erreur">{{ $message }}</p>@enderror

            <form method="POST" action="{{ route('admin.prevention.gestes.proposes.ajouter', $fiche) }}" data-gestes-ia novalidate>
                @csrf
                <div class="pv-ia-etapes">
                    @foreach(\App\Models\GesteCle::MOMENTS as $moment => $libelle)
                        @php $groupe = $propositions->where('moment', $moment); @endphp
                        @if($groupe->isNotEmpty())
                            <div class="pv-ia-etape">
                                <h3><span>{{ $loop->iteration }}</span>{{ $libelle }}</h3>
                                @foreach($groupe as $index => $proposition)
                                    <div class="pv-ia-proposition">
                                        <input type="checkbox" name="gestes[{{ $index }}][choisi]" value="1" aria-label="Garder ce geste" @checked($selectionEnvoyee ? old("gestes.$index.choisi") : true)>
                                        <div>
                                            <textarea name="gestes[{{ $index }}][intitule]" rows="3" maxlength="150" aria-label="Texte du geste" class="@error("gestes.$index.intitule") is-invalid @enderror">{{ old("gestes.$index.intitule", $proposition['intitule']) }}</textarea>
                                            @error("gestes.$index.intitule")<p class="pv-erreur">{{ $message }}</p>@enderror
                                        </div>
                                        <input type="hidden" name="gestes[{{ $index }}][moment]" value="{{ $proposition['moment'] }}">
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </div>

                <div class="pv-ia-pied">
                    <button class="ha-primary-button" type="submit" data-gestes-ia-bouton>Ajouter les gestes cochés</button>
                    <small data-gestes-ia-compte></small>
                </div>
            </form>
        @endif
    </div>

    <div class="pv-ia-chargement" data-ia-chargement role="status" hidden>
        <span class="pv-ia-points" aria-hidden="true"><i></i><i></i><i></i></span>
        <span data-ia-message>{{ $etapes[0] }}</span>
    </div>

    <footer class="pv-ia-pied">
        <form method="POST" action="{{ route('admin.prevention.gestes.proposer', $fiche) }}" data-ia-formulaire data-ia-etapes='@json($etapes)'>
            @csrf
            <button class="{{ $propositions->isEmpty() ? 'ha-primary-button' : 'ha-secondary-button' }}" type="submit">{{ $propositions->isEmpty() ? 'Proposer des gestes' : 'Proposer d’autres gestes' }}</button>
        </form>
        @if($propositions->isNotEmpty())
            <form method="POST" action="{{ route('admin.prevention.gestes.proposes.ignorer', $fiche) }}">
                @csrf @method('DELETE')
                <button class="ha-text-button" type="submit">Ignorer ces propositions</button>
            </form>
        @endif
    </footer>

    <p class="pv-ia-avis">Relisez chaque geste avant de l’ajouter : l’IA peut se tromper.</p>
</section>
