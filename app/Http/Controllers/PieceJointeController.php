<?php

namespace App\Http\Controllers;

use App\Models\PieceJointe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PieceJointeController extends Controller
{
    public function destroy(Request $request, PieceJointe $pieceJointe): RedirectResponse
    {
        abort_unless($request->user()?->role === 'user', 403);
        $signalement = $pieceJointe->signalement;
        Gate::authorize('update', $signalement);

        $pieceJointe->delete();

        return back()->with('success', 'La pièce jointe a été supprimée.');
    }
}
