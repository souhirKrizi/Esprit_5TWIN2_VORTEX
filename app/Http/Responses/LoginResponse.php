<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $space = match ($request->user(config('fortify.guard'))->role) {
            'admin' => 'admin',
            'agent-campagne' => 'agent-campagne',
            default => 'user',
        };

        $url = route('spaces.'.$space);

        return $request->wantsJson()
            ? response()->json(['two_factor' => false, 'redirect' => $url])
            : redirect()->to($url);
    }
}
