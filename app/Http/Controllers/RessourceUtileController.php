<?php

namespace App\Http\Controllers;

use App\Models\RessourceUtile;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RessourceUtileController extends Controller
{
    public function show(Request $request, RessourceUtile $ressourceUtile): View
    {
        abort_unless($request->user()?->role === 'user', 403);

        $ressourceUtile->load([
            'signalements' => fn ($query) => $query->valide()->with('piecesJointes')->latest(),
        ]);

        return view('signalements.ressource', ['ressourceUtile' => $ressourceUtile, 'space' => 'user']);
    }
}
