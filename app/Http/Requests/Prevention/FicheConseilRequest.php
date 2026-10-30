<?php

namespace App\Http\Requests\Prevention;

use App\Models\EquipementSensible;
use App\Models\FicheConseil;
use Illuminate\Validation\Rule;

class FicheConseilRequest extends PreventionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'titre' => ['required', 'string', 'min:5', 'max:150', Rule::unique('fiches_conseils', 'titre')->ignore($this->route('fiche'))],
            'resume' => ['required', 'string', 'min:10', 'max:100'],
            'contenu' => ['required', 'string', 'min:10', 'max:5000'],
            'thematique' => ['required', Rule::in(array_keys(FicheConseil::THEMATIQUES))],
            'niveau_alerte' => ['required', Rule::in(array_keys(FicheConseil::NIVEAUX))],
            'type_equipement' => ['nullable', 'required_if:thematique,equipements', Rule::in(array_keys(EquipementSensible::TYPES))],
            'statut' => ['required', Rule::in(array_keys(FicheConseil::STATUTS))],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'supprimer_image' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'titre' => 'titre',
            'resume' => 'résumé',
            'contenu' => 'contenu',
            'thematique' => 'thématique',
            'niveau_alerte' => 'niveau d’alerte',
            'type_equipement' => 'équipement concerné',
            'statut' => 'statut',
            'image' => 'image',
        ];
    }

    protected function messagesPropres(): array
    {
        return [
            'titre.unique' => 'Une fiche avec ce titre existe déjà.',
            'type_equipement.required_if' => 'Choisissez l’équipement concerné pour la thématique « Équipements sensibles ».',
            'image.image' => 'Le fichier doit être une image.',
            'image.mimes' => 'L’image doit être au format JPG, PNG ou WEBP.',
            'image.max' => 'L’image ne doit pas dépasser 2 Mo.',
            'image.uploaded' => 'L’envoi de l’image a échoué. Essayez avec une image plus légère.',
        ];
    }
}
