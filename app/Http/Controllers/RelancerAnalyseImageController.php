<?php

namespace App\Http\Controllers;

use App\Jobs\AnalyserImagesSignalementJob;
use App\Models\Signalement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RelancerAnalyseImageController extends Controller
{
    public function __invoke(Request $request, Signalement $signalement): RedirectResponse
    {
        abort_unless($request->user()?->role === 'user', 403);
        Gate::authorize('relancerAnalyseImage', $signalement);

        if (!$signalement->piecesJointes()->exists()) {
            return back()->with('error', 'Ce signalement ne comporte aucune image à analyser.');
        }

        if (!config('assistant_ia.vision_enabled', true) || blank(config('assistant_ia.key'))) {
            return back()->with('error', 'Le service d\'analyse IA des images est actuellement désactivé.');
        }

        $signalement->update([
            'ia_image_statut' => Signalement::IA_IMAGE_EN_COURS,
        ]);

        AnalyserImagesSignalementJob::dispatchAfterResponse($signalement->id);

        return back()->with('success', 'L’analyse de l’image a été relancée avec succès.');
    }
}

