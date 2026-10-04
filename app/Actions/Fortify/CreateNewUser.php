<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'governorate' => [
                'required',
                'string',
                Rule::in([
                    'Tunis', 'Ariana', 'Ben Arous', 'Manouba', 'Bizerte', 'Béja',
                    'Jendouba', 'Le Kef', 'Siliana', 'Nabeul', 'Zaghouan', 'Sousse',
                    'Monastir', 'Mahdia', 'Sfax', 'Kairouan', 'Kasserine', 'Sidi Bouzid',
                    'Gabès', 'Médenine', 'Tataouine', 'Gafsa', 'Tozeur', 'Kébili',
                ]),
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*\d).{8,}$/',
            ],
            'terms' => ['accepted'],
            'email_alerts' => ['sometimes', 'boolean'],
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'governorate' => $input['governorate'],
            'password' => Hash::make($input['password']),
            'email_alerts' => (bool) ($input['email_alerts'] ?? false),
            'terms_accepted_at' => now(),
        ]);
    }
}
