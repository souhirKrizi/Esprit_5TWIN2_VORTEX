<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation de la requête de vérification de doublon géographique.
 */
class VerificationDoublonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // L'autorisation (rôle) est vérifiée dans le contrôleur.
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type_signalement' => trim(strip_tags((string) ($this->input('type_signalement') ?? ''))),
            'type_incident'    => $this->filled('type_incident') ? trim(strip_tags((string) $this->input('type_incident'))) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'latitude'         => ['required', 'numeric', 'between:30,38'],
            'longitude'        => ['required', 'numeric', 'between:7,12'],
            'type_signalement' => ['required', 'string', 'in:incident,ressource_ok'],
            'type_incident'    => ['nullable', 'string', 'in:coupure_electricite,point_eau_hors_service,centre_ferme,autre'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required'         => 'La latitude est obligatoire.',
            'latitude.numeric'          => 'La latitude doit être un nombre.',
            'latitude.between'          => 'La latitude doit être comprise entre 30 et 38 (Tunisie).',
            'longitude.required'        => 'La longitude est obligatoire.',
            'longitude.numeric'         => 'La longitude doit être un nombre.',
            'longitude.between'         => 'La longitude doit être comprise entre 7 et 12 (Tunisie).',
            'type_signalement.required' => 'Le type de signalement est obligatoire.',
            'type_signalement.in'       => 'Le type de signalement doit être « incident » ou « ressource_ok ».',
            'type_incident.in'          => 'Le type d\'incident sélectionné n\'est pas valide.',
        ];
    }

    public function attributes(): array
    {
        return [
            'latitude'         => 'latitude',
            'longitude'        => 'longitude',
            'type_signalement' => 'type de signalement',
            'type_incident'    => 'type d\'incident',
        ];
    }
}

