<?php

namespace App\Http\Responses;

use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Symfony\Component\HttpFoundation\Response;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    public function toResponse($request): Response
    {
        $url = $request->user(config('fortify.guard'))->spaceUrl();

        return $request->wantsJson()
            ? response()->json(['redirect' => $url], 201)
            ? response()->json(['redirect' => $url])
            : redirect()->to($url);
    }
}
