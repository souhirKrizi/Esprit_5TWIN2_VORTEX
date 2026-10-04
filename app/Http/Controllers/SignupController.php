<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SignupController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'governorate' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'confirmed', 'regex:/^(?=.*[A-Z])(?=.*\d).{8,}$/'],
            'terms' => ['accepted'],
            'email_alerts' => ['sometimes', 'boolean'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'governorate' => $validated['governorate'],
            'password' => $validated['password'],
            'email_alerts' => $request->boolean('email_alerts'),
            'terms_accepted_at' => now(),
        ]);

        return redirect()->route('signin')->with('status', 'Votre compte a été créé. Vous pouvez maintenant vous connecter.');
    }
}