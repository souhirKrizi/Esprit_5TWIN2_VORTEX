<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class AssistantRedactionRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     * La vérification fine du rôle citoyen est assurée dans le contrôleur.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Préparation des données avant validation :
     * Nettoyage du texte avec trim et strip_tags.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('texte') && is_string($this->input('texte'))) {
            $this->merge([
                'texte' => trim(strip_tags($this->input('texte'))),
            ]);
        }
    }

    /**
     * Règles de validation pour la reformulation.
     */
    public function rules(): array
    {
        return [
            'texte' => ['required', 'string', 'min:5', 'max:1000'],
            'type_signalement' => ['nullable', 'string', 'in:incident,ressource_ok'],
            'type_incident' => ['nullable', 'string', 'in:coupure_electricite,point_eau_hors_service,centre_ferme,autre'],
        ];
    }

    /**
     * Messages de validation personnalisés en français.
     */
    public function messages(): array
    {
        return [
            'texte.required' => 'Veuillez saisir une description à reformuler.',
            'texte.min' => 'La description doit comporter au moins :min caractères pour être reformulée.',
            'texte.max' => 'La description ne peut pas dépasser :max caractères.',
            'type_signalement.in' => 'Le type de signalement sélectionné est invalide.',
            'type_incident.in' => 'Le type d’incident sélectionné est invalide.',
        ];
    }

    /**
     * Noms d'attributs personnalisés en français.
     */
    public function attributes(): array
    {
        return [
            'texte' => 'description',
            'type_signalement' => 'type de signalement',
            'type_incident' => 'type d’incident',
        ];
    }

    /**
     * Réponse JSON 422 standard systématique.
     */
    protected function failedValidation(Validator $validator)
    {
        $response = response()->json([
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422);

        throw new ValidationException($validator, $response);
    }
}

