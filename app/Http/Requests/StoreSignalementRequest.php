<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSignalementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'user';
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'commentaire' => $this->filled('commentaire')
                ? trim(strip_tags((string) $this->input('commentaire')))
                : null,
            'type_incident' => $this->input('type_signalement') === 'incident'
                ? $this->input('type_incident')
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'ressource_utile_id' => ['nullable', 'integer', 'exists:ressources_utiles,id'],
            'type_signalement' => ['required', 'in:incident,ressource_ok'],
            'type_incident' => ['required_if:type_signalement,incident', 'nullable', 'in:coupure_electricite,point_eau_hors_service,centre_ferme,autre'],
            'latitude' => ['required', 'numeric', 'between:30,38'],
            'longitude' => ['required', 'numeric', 'between:7,12'],
            'commentaire' => ['required_if:type_signalement,incident', 'nullable', 'string', 'min:10', 'max:1000'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=200,min_height=200'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('type_signalement') === 'ressource_ok' && $this->filled('type_incident')) {
                $validator->errors()->add('type_incident', 'Le type d’incident doit rester vide lorsque tout fonctionne.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'ressource_utile_id.exists' => 'La ressource sélectionnée n’existe pas.',
            'type_signalement.required' => 'Veuillez choisir le type de signalement.',
            'type_signalement.in' => 'Le type de signalement sélectionné est invalide.',
            'type_incident.required_if' => 'Veuillez préciser le type d’incident.',
            'type_incident.in' => 'Le type d’incident sélectionné est invalide.',
            'latitude.required' => 'Veuillez choisir une position sur la carte.',
            'latitude.numeric' => 'La latitude doit être numérique.',
            'latitude.between' => 'La latitude doit être comprise entre 30 et 38.',
            'longitude.required' => 'Veuillez choisir une position sur la carte.',
            'longitude.numeric' => 'La longitude doit être numérique.',
            'longitude.between' => 'La longitude doit être comprise entre 7 et 12.',
            'commentaire.required_if' => 'Le commentaire est obligatoire pour un incident.',
            'commentaire.min' => 'Le commentaire doit contenir au moins :min caractères.',
            'commentaire.max' => 'Le commentaire ne peut pas dépasser :max caractères.',
            'images.array' => 'Les images envoyées sont invalides.',
            'images.max' => 'Vous ne pouvez pas joindre plus de :max images.',
            'images.*.image' => 'Chaque pièce jointe doit être une image.',
            'images.*.mimes' => 'Les images doivent être au format JPG, JPEG, PNG ou WEBP.',
            'images.*.max' => 'Chaque image ne peut pas dépasser 5 Mo.',
            'images.*.dimensions' => 'Chaque image doit mesurer au moins 200 × 200 pixels.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ressource_utile_id' => 'ressource utile',
            'type_signalement' => 'type de signalement',
            'type_incident' => 'type d’incident',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'commentaire' => 'commentaire',
            'images' => 'images',
            'images.*' => 'image',
        ];
    }
}
