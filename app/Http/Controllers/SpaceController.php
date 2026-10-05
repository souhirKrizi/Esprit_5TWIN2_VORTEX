<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpaceController extends Controller
{
    public function redirect(Request $request): RedirectResponse
    {
        return redirect()->to($request->user()->spaceUrl());
    }

    public function show(Request $request, User $user): View
    {
        $space = $request->route()->defaults['space'];

        abort_unless($user->is($request->user()) && $request->user()->role === $space, 403);

        return view('spaces.index', ['space' => $space, 'user' => $request->user()]);
    }
}
