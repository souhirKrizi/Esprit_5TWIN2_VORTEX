<?php

namespace App\Http\Requests\Profil;

use App\Http\Requests\Prevention\PreventionRequest;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InformationsProfilRequest extends PreventionRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower($this->input('email'))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user())],
            'governorate' => ['required', Rule::in(User::GOUVERNORATS)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'supprimer_photo' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom complet',
            'email' => 'adresse e-mail',
            'governorate' => 'gouvernorat',
            'photo' => 'photo de profil',
        ];
    }

    protected function messagesPropres(): array
    {
        return [
            'email.email' => 'Saisissez une adresse e-mail valide.',
            'email.unique' => 'Cette adresse e-mail est déjà utilisée par un autre compte.',
            'photo.image' => 'Le fichier doit être une image.',
            'photo.mimes' => 'La photo doit être au format JPG, PNG ou WEBP.',
            'photo.max' => 'La photo ne doit pas dépasser 2 Mo.',
            'photo.uploaded' => 'L’envoi de la photo a échoué. Essayez avec une image plus légère.',
        ];
    }
}
