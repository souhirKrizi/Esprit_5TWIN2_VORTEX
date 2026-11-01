<?php

namespace App\Http\Requests\Profil;

use App\Http\Requests\Prevention\PreventionRequest;

class ArchivageCompteRequest extends PreventionRequest
{
    protected $errorBag = 'archivage';

    public function rules(): array
    {
        return [
            'mot_de_passe' => ['required', 'string', 'current_password'],
        ];
    }

    public function attributes(): array
    {
        return ['mot_de_passe' => 'mot de passe'];
    }

    protected function messagesPropres(): array
    {
        return ['mot_de_passe.current_password' => 'Le mot de passe est incorrect.'];
    }

    protected function getRedirectUrl(): string
    {
        return parent::getRedirectUrl().'#suppression';
    }
}
