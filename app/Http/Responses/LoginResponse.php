<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $url = $request->user(config('fortify.guard'))->spaceUrl();

        return $request->wantsJson()
            ? response()->json(['two_factor' => false, 'redirect' => $url])
            : redirect()->to($url);
    }
}
