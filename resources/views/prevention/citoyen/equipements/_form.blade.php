<div class="pv-grid-2">
    <div class="pv-field pv-field-wide">
        <label for="nom">Nom <span>*</span></label>
        <input id="nom" name="nom" value="{{ old('nom', $equipement->nom) }}" maxlength="80" class="@error('nom') is-invalid @enderror" placeholder="Exemple : Frigo de la cuisine">
        @error('nom')<p class="pv-erreur">{{ $message }}</p>@enderror
    </div>

    <div class="pv-field">
        <label for="type">Type <span>*</span></label>
        <select id="type" name="type" class="@error('type') is-invalid @enderror">
            <option value="">Choisir…</option>
            @foreach(\App\Models\EquipementSensible::TYPES as $valeur => $libelle)
                <option value="{{ $valeur }}" @selected(old('type', $equipement->type) === $valeur)>{{ $libelle }}</option>
            @endforeach
        </select>
        @error('type')<p class="pv-erreur">{{ $message }}</p>@enderror
    </div>

    <div class="pv-field">
        <label for="sensibilite">Sensible à <span>*</span></label>
        <select id="sensibilite" name="sensibilite" class="@error('sensibilite') is-invalid @enderror">
            <option value="">Choisir…</option>
            @foreach(\App\Models\EquipementSensible::SENSIBILITES as $valeur => $libelle)
                <option value="{{ $valeur }}" @selected(old('sensibilite', $equipement->sensibilite) === $valeur)>{{ $libelle }}</option>
            @endforeach
        </select>
        @error('sensibilite')<p class="pv-erreur">{{ $message }}</p>@enderror
    </div>

    <div class="pv-field">
        <label for="autonomie_heures">Autonomie sans courant (en heures)</label>
        <input id="autonomie_heures" name="autonomie_heures" type="number" value="{{ old('autonomie_heures', $equipement->autonomie_heures) }}" class="@error('autonomie_heures') is-invalid @enderror" placeholder="Exemple : 4">
        @error('autonomie_heures')<p class="pv-erreur">{{ $message }}</p>@enderror
        <small>Facultatif. Entre 0 et 72 heures.</small>
    </div>

    <div class="pv-field pv-field-wide">
        <label for="description">Description du problème</label>
        <textarea id="description" name="description" rows="4" maxlength="500" class="@error('description') is-invalid @enderror" placeholder="Exemple : le frigo contient de l’insuline et s’arrête à chaque coupure de courant.">{{ old('description', $equipement->description) }}</textarea>
        @error('description')<p class="pv-erreur">{{ $message }}</p>@enderror
        <small>Facultatif. Entre 10 et 500 caractères.</small>
    </div>
</div>
