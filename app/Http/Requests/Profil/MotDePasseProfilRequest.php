<?php

namespace App\Http\Requests\Profil;

use App\Http\Requests\Prevention\PreventionRequest;

class MotDePasseProfilRequest extends PreventionRequest
{
    protected $errorBag = 'motDePasse';

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            // Mêmes exigences qu'à l'inscription.
            'password' => ['required', 'string', 'confirmed', 'regex:/^(?=.*[A-Z])(?=.*\d).{8,}$/', 'different:current_password'],
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => 'mot de passe actuel',
            'password' => 'nouveau mot de passe',
        ];
    }

    protected function messagesPropres(): array
    {
        return [
            'current_password.current_password' => 'Le mot de passe actuel est incorrect.',
            'password.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
            'password.regex' => 'Le mot de passe doit contenir au moins 8 caractères, une majuscule et un chiffre.',
            'password.different' => 'Le nouveau mot de passe doit être différent de l’actuel.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return parent::getRedirectUrl().'#mot-de-passe';
    }
}
