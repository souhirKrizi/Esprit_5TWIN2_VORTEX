<div class="pv-grid-2">
    <div class="pv-field pv-field-wide">
        <label for="intitule">Geste <span>*</span></label>
        <input id="intitule" name="intitule" value="{{ old('intitule', $geste->intitule) }}" maxlength="150" class="@error('intitule') is-invalid @enderror" placeholder="Exemple : Remplir des bouteilles d’eau au frais">
        @error('intitule')<p class="pv-erreur">{{ $message }}</p>@enderror
    </div>

    <div class="pv-field">
        <label for="moment">Moment <span>*</span></label>
        <select id="moment" name="moment" class="@error('moment') is-invalid @enderror">
            @foreach(\App\Models\GesteCle::MOMENTS as $valeur => $libelle)
                <option value="{{ $valeur }}" @selected(old('moment', $geste->moment) === $valeur)>{{ $libelle }} la canicule ou la coupure</option>
            @endforeach
        </select>
        @error('moment')<p class="pv-erreur">{{ $message }}</p>@enderror
    </div>

    <div class="pv-field">
        <label for="ordre">Ordre <span>*</span></label>
        <input id="ordre" name="ordre" type="number" value="{{ old('ordre', $geste->ordre) }}" class="@error('ordre') is-invalid @enderror">
        @error('ordre')<p class="pv-erreur">{{ $message }}</p>@enderror
        <small>Entre 1 et 20.</small>
    </div>
</div>
