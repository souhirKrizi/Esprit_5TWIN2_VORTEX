<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class PasswordResetCodeController extends Controller
{
    private const CODE_TTL_MINUTES = 15;

    private const RESEND_DELAY_SECONDS = 60;

    private const MAX_CODE_ATTEMPTS = 5;

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ], [
            'email.required' => 'L’adresse e-mail est obligatoire.',
            'email.email' => 'Veuillez saisir une adresse e-mail valide.',
            'email.max' => 'L’adresse e-mail est trop longue.',
        ]);

        $email = Str::lower(trim($data['email']));
        $user = User::whereRaw('lower(email) = ?', [$email])->first();

        if (! $user) {
            return back()->withInput()->withErrors([
                'email' => 'Aucun compte HeatAlert n’est associé à cette adresse e-mail.',
            ]);
        }

        $existing = DB::table('password_reset_tokens')->where('email', $email)->first();

        if ($existing && Carbon::parse($existing->created_at)->addSeconds(self::RESEND_DELAY_SECONDS)->isFuture()) {
            return redirect()->route('password.reset', ['email' => $email])->with(
                'status',
                'Un code vous a déjà été envoyé il y a moins d’une minute. Vérifiez votre boîte de réception (et les spams) avant d’en demander un nouveau.'
            );
        }

        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $email],
            ['token' => Hash::make($code), 'created_at' => now()],
        );
        RateLimiter::clear($this->attemptsKey($email));

        try {
            Mail::to($user->email)->send(new PasswordResetCode($code, $user->name, self::CODE_TTL_MINUTES));
        } catch (Throwable $e) {
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            Log::error('Échec d’envoi du code de réinitialisation', ['email' => $email, 'error' => $e->getMessage()]);

            return back()->withInput()->withErrors([
                'email' => 'L’envoi de l’e-mail a échoué. Réessayez dans quelques instants.',
            ]);
        }

        return redirect()->route('password.reset', ['email' => $email])->with(
            'status',
            'Un code à 6 chiffres vient d’être envoyé à '.$user->email.'. Il est valable '.self::CODE_TTL_MINUTES.' minutes.'
        );
    }

    public function edit(Request $request): View
    {
        return view('auth.reset-password', ['email' => (string) $request->query('email', '')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'regex:/[A-Z]/', 'confirmed'],
        ], [
            'email.required' => 'L’adresse e-mail est obligatoire.',
            'email.email' => 'Veuillez saisir une adresse e-mail valide.',
            'email.max' => 'L’adresse e-mail est trop longue.',
            'code.required' => 'Le code de vérification est obligatoire.',
            'code.digits' => 'Le code doit contenir exactement 6 chiffres.',
            'password.required' => 'Le nouveau mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.regex' => 'Le mot de passe doit contenir au moins une lettre majuscule.',
            'password.confirmed' => 'La confirmation ne correspond pas au mot de passe saisi.',
        ]);

        $email = Str::lower(trim($data['email']));
        $attemptsKey = $this->attemptsKey($email);

        if (RateLimiter::tooManyAttempts($attemptsKey, self::MAX_CODE_ATTEMPTS)) {
            $minutes = (int) ceil(RateLimiter::availableIn($attemptsKey) / 60);

            return back()->withInput($request->only('email'))->withErrors([
                'code' => "Trop de tentatives. Réessayez dans {$minutes} minute(s) ou demandez un nouveau code.",
            ]);
        }

        $record = DB::table('password_reset_tokens')->where('email', $email)->first();
        $user = User::whereRaw('lower(email) = ?', [$email])->first();

        $isValid = $record
            && $user
            && Carbon::parse($record->created_at)->addMinutes(self::CODE_TTL_MINUTES)->isFuture()
            && Hash::check($data['code'], $record->token);

        if (! $isValid) {
            RateLimiter::hit($attemptsKey, self::CODE_TTL_MINUTES * 60);

            return back()->withInput($request->only('email'))->withErrors([
                'code' => 'Code invalide ou expiré. Vérifiez le code reçu ou demandez-en un nouveau.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),
        ])->save();

        DB::table('password_reset_tokens')->where('email', $email)->delete();
        RateLimiter::clear($attemptsKey);

        return redirect()->route('signin')->with('status', 'Votre mot de passe a été réinitialisé. Vous pouvez maintenant vous connecter.');
    }

    private function attemptsKey(string $email): string
    {
        return 'password-reset-code:'.$email;
    }
}
