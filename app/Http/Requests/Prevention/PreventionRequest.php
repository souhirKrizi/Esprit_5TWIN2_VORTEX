<?php

namespace App\Http\Requests\Prevention;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Messages d'erreur en français communs aux formulaires du module.
 */
abstract class PreventionRequest extends FormRequest
{
    public function messages(): array
    {
        return array_merge([
            'required' => 'Le champ « :attribute » est obligatoire.',
            'string' => 'Le champ « :attribute » doit être un texte.',
            'integer' => 'Le champ « :attribute » doit être un nombre entier.',
            'in' => 'Choisissez une valeur valide pour « :attribute ».',
            'unique' => 'Ce « :attribute » existe déjà.',
            'min' => [
                'string' => 'Le champ « :attribute » doit contenir au moins :min caractères.',
                'numeric' => 'Le champ « :attribute » doit être au moins :min.',
            ],
            'max' => [
                'string' => 'Le champ « :attribute » ne doit pas dépasser :max caractères.',
                'numeric' => 'Le champ « :attribute » ne doit pas dépasser :max.',
            ],
        ], $this->messagesPropres());
    }

    /** Messages propres à un formulaire. */
    protected function messagesPropres(): array
    {
        return [];
    }
}
