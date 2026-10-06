<?php

namespace App\Http\Controllers;

use App\Models\Campagne;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminMissionController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $campagnes = Campagne::with([
            'organisateur:id,name',
            'missions' => fn ($query) => $query->orderBy('date_debut')->orderBy('id'),
        ])->latest()->orderByDesc('id')->get();

        $totalMissions = $campagnes->sum(fn ($campagne) => $campagne->missions->count());
        $missionsEnCours = $campagnes->sum(fn ($campagne) => $campagne->missions->where('statut', 'en_cours')->count());

        return view('admin.missions.index', compact('campagnes', 'totalMissions', 'missionsEnCours'));
    }
}
