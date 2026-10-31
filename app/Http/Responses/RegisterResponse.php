<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        $url = $request->user(config('fortify.guard'))->spaceUrl();

        return $request->wantsJson()
            ? response()->json(['redirect' => $url], 201)
            : redirect()->to($url);
    }
}
