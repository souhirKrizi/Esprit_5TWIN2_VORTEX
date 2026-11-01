<?php

namespace App\Http\Controllers;

use App\Models\Campagne;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminParticipationController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $campagnes = Campagne::with([
            'organisateur:id,name',
            'missions' => fn ($query) => $query->orderBy('date_debut')->orderBy('id'),
            'missions.participations' => fn ($query) => $query->orderBy('id'),
            'missions.participations.user:id,name,email',
        ])->orderByDesc('date_debut')->orderByDesc('id')->paginate(10);

        return view('admin.participants.index', ['space' => 'admin', 'campagnes' => $campagnes]);
    }
}
