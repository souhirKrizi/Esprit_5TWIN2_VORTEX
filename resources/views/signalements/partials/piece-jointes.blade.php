@if($signalement->piecesJointes->isNotEmpty())
    <div class="ha-attachment-grid">
        @foreach($signalement->piecesJointes as $pieceJointe)
            <figure class="ha-attachment" data-attachment>
                <img src="{{ asset('storage/'.$pieceJointe->chemin_fichier) }}" alt="{{ $pieceJointe->nom_original }}">
                @if($signalement->statut_validation === 'en_attente')
                    <button class="ha-icon-button ha-attachment-remove" type="button" data-attachment-delete data-delete-action="{{ route('user.pieces-jointes.destroy', $pieceJointe) }}" data-csrf-token="{{ csrf_token() }}" aria-label="Supprimer {{ $pieceJointe->nom_original }}">×</button>
                @endif
                <figcaption>{{ $pieceJointe->nom_original }}</figcaption>
            </figure>
        @endforeach
    </div>
@endif
