<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profil\ArchivageCompteRequest;
use App\Http\Requests\Profil\InformationsProfilRequest;
use App\Http\Requests\Profil\MotDePasseProfilRequest;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * « Mon profil » du citoyen (/user/profil), de l'agent de campagne (/agent-campagne/profil) et de l'administrateur (/admin/profil).
 */
class ProfilController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(function (Request $request, Closure $next) {
                abort_unless($request->user()?->role === $request->segment(1), 403);

                return $next($request);
            }),
        ];
    }

    public function edit(Request $request): View
    {
        return view('profil.edit', ['compte' => $request->user()]);
    }

    public function update(InformationsProfilRequest $request): RedirectResponse
    {
        $compte = $request->user();
        $anciennePhoto = $compte->photo;
        $donnees = $request->safe()->only(['name', 'email', 'governorate']);

        if ($request->hasFile('photo')) {
            $donnees['photo'] = $request->file('photo')->store('profils', 'public');
        } elseif ($request->boolean('supprimer_photo')) {
            $donnees['photo'] = null;
        }

        $compte->update($donnees);

        if ($anciennePhoto && $anciennePhoto !== $compte->photo) {
            Storage::disk('public')->delete($anciennePhoto);
        }

        return redirect()->route($compte->role.'.profil.edit')->with('success', 'Vos informations ont été modifiées.');
    }

    public function updatePassword(MotDePasseProfilRequest $request): RedirectResponse
    {
        $compte = $request->user();
        $compte->password = $request->validated('password');
        $compte->setRememberToken(Str::random(60));
        $compte->save();

        // Un autre appareil connecté avec l'ancien mot de passe doit se reconnecter.
        if (config('session.driver') === 'database') {
            DB::table(config('session.table'))
                ->where('user_id', $compte->getKey())
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        return redirect()->route($compte->role.'.profil.edit')->with('success', 'Votre mot de passe a été modifié.');
    }

    /**
     * La suppression demandée par l'utilisateur archive le compte : ses données restent, et l'administrateur peut le restaurer.
     */
    public function destroy(ArchivageCompteRequest $request): RedirectResponse
    {
        $compte = $request->user();
        $compte->archived = true;
        $compte->setRememberToken(Str::random(60));
        $compte->save();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('signin')->with('status', 'Votre compte a été supprimé. Pour le récupérer, contactez l’administrateur.');
    }
}
