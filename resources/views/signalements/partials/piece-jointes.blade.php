@if($signalement->piecesJointes->isNotEmpty())
    <div class="ha-attachment-grid">
        @foreach($signalement->piecesJointes as $pieceJointe)
            <figure class="ha-attachment" data-attachment>
                <img src="{{ asset('storage/'.$pieceJointe->chemin_fichier) }}" alt="{{ $pieceJointe->nom_original }}">
                @if($signalement->statut_validation === 'en_attente')
                    <form method="POST" action="{{ route('user.pieces-jointes.destroy', $pieceJointe) }}" data-attachment-delete>
                        @csrf
                        @method('DELETE')
                        <button class="ha-icon-button ha-attachment-remove" type="submit" aria-label="Supprimer {{ $pieceJointe->nom_original }}">×</button>
                    </form>
                @endif
                <figcaption>{{ $pieceJointe->nom_original }}</figcaption>
            </figure>
        @endforeach
    </div>
@endif
