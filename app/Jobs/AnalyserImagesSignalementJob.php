<?php

namespace App\Jobs;

use App\Exceptions\AnalyseImageIndisponibleException;
use App\Models\Signalement;
use App\Services\AnalyseImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnalyserImagesSignalementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 35;

    public function __construct(
        public readonly int $signalementId
    ) {
        $this->timeout = (int) config('assistant_ia.vision_timeout', 30) + 5;
    }

    public function handle(AnalyseImageService $service): void
    {
        $signalement = Signalement::with('piecesJointes')->find($this->signalementId);

        if (!$signalement || $signalement->piecesJointes->isEmpty()) {
            return;
        }

        try {
            $resultat = $service->analyser($signalement);

            $signalement->update([
                'ia_image_statut'     => Signalement::IA_IMAGE_ANALYSE,
                'ia_image_pertinente' => $resultat['pertinente'],
                'ia_image_confiance'  => $resultat['confiance'],
                'ia_image_categorie'  => $resultat['categorie'],
                'ia_image_resume'     => $resultat['resume'],
                'ia_image_analyse_le' => now(),
            ]);
        } catch (AnalyseImageIndisponibleException $e) {
            $signalement->update([
                'ia_image_statut'     => Signalement::IA_IMAGE_INDISPONIBLE,
                'ia_image_analyse_le' => now(),
            ]);
        } catch (Throwable $e) {
            Log::warning('AnalyserImagesSignalementJob : échec inattendu', [
                'signalement_id' => $this->signalementId,
                'erreur'         => $e->getMessage(),
            ]);

            $signalement->update([
                'ia_image_statut'     => Signalement::IA_IMAGE_INDISPONIBLE,
                'ia_image_analyse_le' => now(),
            ]);
        }
    }
}

