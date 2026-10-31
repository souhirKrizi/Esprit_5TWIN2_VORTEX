<div class="pv-grid-2">
    <div class="pv-field pv-field-wide">
        <label for="titre">Titre <span>*</span></label>
        <input id="titre" name="titre" value="{{ old('titre', $fiche->titre) }}" maxlength="150" class="@error('titre') is-invalid @enderror" placeholder="Exemple : Bien s’hydrater pendant la canicule">
        @error('titre')<p class="pv-erreur">{{ $message }}</p>@enderror
    </div>

    <div class="pv-field pv-field-wide">
        <label for="resume">Résumé <span>*</span></label>
        <input id="resume" name="resume" value="{{ old('resume', $fiche->resume) }}" maxlength="100" class="@error('resume') is-invalid @enderror" placeholder="Une phrase qui résume le conseil">
        @error('resume')<p class="pv-erreur">{{ $message }}</p>@enderror
        <small>Entre 10 et 100 caractères.</small>
    </div>

    <div class="pv-field pv-field-wide">
        <label for="contenu">Contenu <span>*</span></label>
        <textarea id="contenu" name="contenu" rows="8" maxlength="5000" class="@error('contenu') is-invalid @enderror">{{ old('contenu', $fiche->contenu) }}</textarea>
        @error('contenu')<p class="pv-erreur">{{ $message }}</p>@enderror
        <small>Au moins 10 caractères.</small>
    </div>

    <div class="pv-field">
        <label for="thematique">Thématique <span>*</span></label>
        <select id="thematique" name="thematique" class="@error('thematique') is-invalid @enderror">
            <option value="">Choisir…</option>
            @foreach(\App\Models\FicheConseil::THEMATIQUES as $valeur => $libelle)
                <option value="{{ $valeur }}" @selected(old('thematique', $fiche->thematique) === $valeur)>{{ $libelle }}</option>
            @endforeach
        </select>
        @error('thematique')<p class="pv-erreur">{{ $message }}</p>@enderror
    </div>

    <div class="pv-field">
        <label for="niveau_alerte">Niveau d’alerte <span>*</span></label>
        <select id="niveau_alerte" name="niveau_alerte" class="@error('niveau_alerte') is-invalid @enderror">
            <option value="">Choisir…</option>
            @foreach(\App\Models\FicheConseil::NIVEAUX as $valeur => $libelle)
                <option value="{{ $valeur }}" @selected(old('niveau_alerte', $fiche->niveau_alerte) === $valeur)>{{ $libelle }}</option>
            @endforeach
        </select>
        @error('niveau_alerte')<p class="pv-erreur">{{ $message }}</p>@enderror
    </div>

    <div class="pv-field">
        <label for="type_equipement">Équipement concerné</label>
        <select id="type_equipement" name="type_equipement" class="@error('type_equipement') is-invalid @enderror">
            <option value="">Aucun</option>
            @foreach(\App\Models\EquipementSensible::TYPES as $valeur => $libelle)
                <option value="{{ $valeur }}" @selected(old('type_equipement', $fiche->type_equipement) === $valeur)>{{ $libelle }}</option>
            @endforeach
        </select>
        @error('type_equipement')<p class="pv-erreur">{{ $message }}</p>@enderror
        <small>Obligatoire pour la thématique « Équipements sensibles ».</small>
    </div>

    <div class="pv-field">
        <label for="statut">Statut <span>*</span></label>
        <select id="statut" name="statut" class="@error('statut') is-invalid @enderror">
            @foreach(\App\Models\FicheConseil::STATUTS as $valeur => $libelle)
                <option value="{{ $valeur }}" @selected(old('statut', $fiche->statut) === $valeur)>{{ $libelle }}</option>
            @endforeach
        </select>
        @error('statut')<p class="pv-erreur">{{ $message }}</p>@enderror
        <small>Seules les fiches publiées sont visibles par les habitants.</small>
    </div>

    <div class="pv-field pv-field-wide">
        <label for="image">Image</label>
        @if($fiche->image)
            <img class="pv-image-apercu" src="{{ $fiche->imageUrl() }}" alt="Image actuelle de la fiche">
        @endif
        <input id="image" name="image" type="file" accept=".jpg,.jpeg,.png,.webp" class="@error('image') is-invalid @enderror">
        @error('image')<p class="pv-erreur">{{ $message }}</p>@enderror
        <small>Facultatif. JPG, PNG ou WEBP, 2 Mo maximum.{{ $fiche->image ? ' Choisissez un fichier pour remplacer l’image actuelle.' : '' }}</small>
        @if($fiche->image)
            <label class="pv-case"><input type="checkbox" name="supprimer_image" value="1" @checked(old('supprimer_image'))> Supprimer l’image actuelle</label>
        @endif
    </div>
</div>
