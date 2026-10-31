<?php

namespace App\Jobs;

use App\Exceptions\AnalyseIaIndisponibleException;
use App\Models\Signalement;
use App\Services\AnalyseFiabiliteService;
use App\Services\DetectionDoublonService;
use App\Services\ScoreSuspicionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Job déclenché après la réponse HTTP (afterResponse) pour analyser
 * la fiabilité d'un signalement sans ralentir le citoyen.
 *
 * Enchaîne les trois couches de détection :
 *   1. Doublons géographiques (DetectionDoublonService)
 *   2. Score anti-spam (ScoreSuspicionService)
 *   3. Analyse IA (AnalyseFiabiliteService) — optionnelle, ne bloque pas
 */
class AnalyserSignalementJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly int $signalementId) {}

    public function handle(
        DetectionDoublonService $doublonService,
        ScoreSuspicionService   $scoreService,
        AnalyseFiabiliteService $iaService,
    ): void {
        try {
            $signalement = Signalement::with('ressourceUtile')->find($this->signalementId);

            if (!$signalement) {
                return;
            }

            // Couche 1 : doublons géographiques
            $similaires   = $doublonService->trouverSimilaires(
                lat:             (float) $signalement->latitude,
                lng:             (float) $signalement->longitude,
                typeSignalement: $signalement->type_signalement,
                typeIncident:    $signalement->type_incident,
                excludeId:       $signalement->id,
            );

            $motifsDuplons = [];
            $doublonDeId   = null;

            if ($similaires->isNotEmpty()) {
                $plusAncien  = $similaires->sortBy('created_at')->first();
                $doublonDeId = $plusAncien?->id;
                $nbCitoyens  = $similaires->pluck('user_id')->unique()->count();
                $motifsDuplons[] = sprintf(
                    '%d signalement(s) similaire(s) à moins de %dm (signalé par %d citoyen(s) différent(s))',
                    $similaires->count(),
                    (int) config('assistant_ia.doublons_rayon_m', 200),
                    $nbCitoyens,
                );
            }

            // Couche 2 : règles anti-spam
            $scoreRegles = $scoreService->calculer($signalement);

            // Couche 3 : analyse IA (optionnelle — ne bloque pas en cas d'échec)
            $analyseStatut = Signalement::ANALYSE_ANALYSE;
            $motifsIa      = [];
            $scoreIa       = null;

            try {
                $resultIa = $iaService->analyser($signalement);
                $scoreIa  = $resultIa['score'];
                if (!empty($resultIa['raison'])) {
                    $motifsIa[] = 'IA : ' . $resultIa['raison'];
                }
                if ($resultIa['verdict'] === 'spam') {
                    $motifsIa[] = 'IA : signalement classifié comme spam';
                } elseif ($resultIa['verdict'] === 'douteux') {
                    $motifsIa[] = 'IA : description jugée douteuse ou peu plausible';
                }
            } catch (AnalyseIaIndisponibleException) {
                $analyseStatut = Signalement::ANALYSE_INDISPONIBLE;
            }

            // Score final : 60 % IA + 40 % règles si IA disponible, sinon 100 % règles
            $scoreFinal = $scoreIa !== null
                ? (int) round(0.4 * $scoreRegles['score'] + 0.6 * $scoreIa)
                : $scoreRegles['score'];

            $scoreFinal = min(100, $scoreFinal);

            // Fusion des motifs
            $tousMotifs = array_merge($motifsDuplons, $scoreRegles['motifs'], $motifsIa);

            // Recalcul du niveau avec le score final
            $niveauFinal = $this->determinerNiveau($scoreFinal);

            // Persistance
            $signalement->update([
                'score_suspicion'    => $scoreFinal,
                'niveau_suspicion'   => $niveauFinal,
                'motifs_suspicion'   => $tousMotifs ?: null,
                'doublon_de_id'      => $doublonDeId,
                'analyse_ia_statut'  => $analyseStatut,
                'analyse_at'         => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('AnalyserSignalementJob: erreur inattendue lors de l\'analyse', [
                'signalement_id' => $this->signalementId,
                'exception'      => $e->getMessage(),
            ]);
        }
    }

    private function determinerNiveau(int $score): string
    {
        if ($score >= 60) {
            return Signalement::NIVEAU_ELEVE;
        }
        if ($score >= 30) {
            return Signalement::NIVEAU_MOYEN;
        }

        return Signalement::NIVEAU_FAIBLE;
    }
}

