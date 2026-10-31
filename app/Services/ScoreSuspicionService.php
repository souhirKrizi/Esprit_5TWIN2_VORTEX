<?php

namespace App\Services;

use App\Models\Signalement;
use App\Models\User;

/**
 * Couche 2 : Score anti-spam basé sur des règles métier.
 *
 * Retourne un tableau ['score' => int, 'niveau' => string, 'motifs' => string[]]
 * où score ∈ [0, 100], niveau ∈ {'faible', 'moyen', 'eleve'}.
 */
class ScoreSuspicionService
{
    // --- Poids des règles (modifiables ici sans toucher à la logique) ---
    private const POIDS_URL_EMAIL_TEL    = 30;
    private const POIDS_REPETITION       = 25;
    private const POIDS_MAJUSCULES       = 15;
    private const POIDS_COPIER_COLLER    = 30;
    private const POIDS_RAFALE           = 25;
    private const POIDS_RESSOURCE_ELOIGNEE = 20;
    private const POIDS_HISTORIQUE_REJETS  = 20;

    // --- Seuils de niveau ---
    private const SEUIL_MOYEN = 30;
    private const SEUIL_ELEVE = 60;

    // --- Paramètres de règles ---
    private const RAFALE_MAX_PAR_HEURE  = 5;
    private const RAFALE_FENETRE_MIN    = 60;
    private const COPIER_COLLER_SEUIL   = 90;   // similar_text() > 90 %
    private const RATIO_MOTS_UNIQUES    = 0.4;   // ratio mots uniques < 40 %
    private const REPETITION_CHAR_N     = 5;     // N caractères identiques de suite
    private const RESSOURCE_ELOIGNEE_M  = 1000;  // >1 km
    private const HISTORIQUE_MIN_TOTAL  = 3;
    private const HISTORIQUE_RATIO_REJETS = 50;  // > 50 %

    /**
     * Calcule le score de suspicion d'un signalement.
     *
     * @return array{score: int, niveau: string, motifs: string[]}
     */
    public function calculer(Signalement $signalement): array
    {
        $score  = 0;
        $motifs = [];

        $commentaire = $signalement->commentaire ?? '';
        $userId      = $signalement->user_id;

        // Règle 1 : URL, e-mail ou téléphone dans le commentaire
        if ($this->contientContactExterieur($commentaire)) {
            $score  += self::POIDS_URL_EMAIL_TEL;
            $motifs[] = 'URL, e-mail ou numéro de téléphone dans la description';
        }

        // Règle 2 : Caractère répété ou ratio de mots uniques très faible
        if ($this->estRepetitif($commentaire)) {
            $score  += self::POIDS_REPETITION;
            $motifs[] = 'Texte répétitif ou ratio de mots uniques insuffisant';
        }

        // Règle 3 : Tout en majuscules ou aucune lettre
        if ($this->estMajusculesOuSansLettre($commentaire)) {
            $score  += self::POIDS_MAJUSCULES;
            $motifs[] = 'Description entièrement en majuscules ou sans lettre';
        }

        // Règle 4 : Quasi-copie d'un autre signalement du même utilisateur
        if ($this->estCopiee($signalement, $userId, $commentaire)) {
            $score  += self::POIDS_COPIER_COLLER;
            $motifs[] = 'Description identique ou quasi identique à un signalement précédent';
        }

        // Règle 5 : Rafale (> N signalements dans la dernière heure)
        if ($this->estRafale($userId, $signalement->id)) {
            $score  += self::POIDS_RAFALE;
            $motifs[] = 'Plus de ' . self::RAFALE_MAX_PAR_HEURE . ' signalements envoyés dans la dernière heure';
        }

        // Règle 6 : Ressource choisie mais position très éloignée
        if ($this->ressourceEloignee($signalement)) {
            $score  += self::POIDS_RESSOURCE_ELOIGNEE;
            $motifs[] = 'Position à plus de 1 km de la ressource sélectionnée';
        }

        // Règle 7 : Historique avec plus de 50 % de rejetés
        if ($this->historiqueNegatif($userId)) {
            $score  += self::POIDS_HISTORIQUE_REJETS;
            $motifs[] = 'Historique : plus de 50 % des signalements précédents rejetés';
        }

        $score  = min(100, $score);
        $niveau = $this->determinerNiveau($score);

        return compact('score', 'niveau', 'motifs');
    }

    // ---------- Méthodes privées des règles ----------

    private function contientContactExterieur(string $texte): bool
    {
        if (empty($texte)) {
            return false;
        }

        // URL
        if (preg_match('#https?://|www\.#i', $texte)) {
            return true;
        }
        // E-mail
        if (preg_match('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $texte)) {
            return true;
        }
        // Téléphone (séquence de 8+ chiffres, éventuellement séparés par espace/tiret/point)
        if (preg_match('/(?:\+?[\d][\d\s.\-]{6,}[\d])/', $texte)) {
            return true;
        }

        return false;
    }

    private function estRepetitif(string $texte): bool
    {
        if (empty($texte)) {
            return false;
        }

        // Caractère répété N fois de suite
        if (preg_match('/(.)\1{' . (self::REPETITION_CHAR_N - 1) . ',}/u', $texte)) {
            return true;
        }

        // Ratio de mots uniques
        $mots   = preg_split('/\s+/u', mb_strtolower(trim($texte)), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $total  = count($mots);
        $uniques = count(array_unique($mots));

        return $total >= 5 && ($uniques / $total) < self::RATIO_MOTS_UNIQUES;
    }

    private function estMajusculesOuSansLettre(string $texte): bool
    {
        if (empty($texte)) {
            return false;
        }

        // Aucune lettre dans le texte
        if (!preg_match('/\p{L}/u', $texte)) {
            return true;
        }

        // Entièrement en majuscules (lettres uniquement)
        $lettres = preg_replace('/[^\p{L}]/u', '', $texte);

        return $lettres !== '' && mb_strtoupper($lettres) === $lettres;
    }

    private function estCopiee(Signalement $signalement, int $userId, string $commentaire): bool
    {
        if (mb_strlen($commentaire) < 10) {
            return false;
        }

        $autresCommentaires = Signalement::query()
            ->where('user_id', $userId)
            ->whereNotNull('commentaire')
            ->when($signalement->exists, fn ($q) => $q->where('id', '!=', $signalement->id))
            ->limit(50)
            ->pluck('commentaire');

        foreach ($autresCommentaires as $autre) {
            similar_text($commentaire, (string) $autre, $pct);
            if ($pct > self::COPIER_COLLER_SEUIL) {
                return true;
            }
        }

        return false;
    }

    private function estRafale(int $userId, ?int $excludeId = null): bool
    {
        return Signalement::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subMinutes(self::RAFALE_FENETRE_MIN))
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->count() > self::RAFALE_MAX_PAR_HEURE;
    }

    private function ressourceEloignee(Signalement $signalement): bool
    {
        if (!$signalement->ressource_utile_id) {
            return false;
        }

        $ressource = $signalement->ressourceUtile;
        if (!$ressource || !$ressource->latitude || !$ressource->longitude) {
            return false;
        }

        $distance = $this->haversineM(
            (float) $signalement->latitude,
            (float) $signalement->longitude,
            (float) $ressource->latitude,
            (float) $ressource->longitude,
        );

        return $distance > self::RESSOURCE_ELOIGNEE_M;
    }

    private function historiqueNegatif(int $userId): bool
    {
        $total   = Signalement::query()->where('user_id', $userId)->count();
        $rejetes = Signalement::query()->where('user_id', $userId)
            ->where('statut_validation', Signalement::STATUT_REJETE)
            ->count();

        return $total >= self::HISTORIQUE_MIN_TOTAL
            && $rejetes > 0
            && ($rejetes / $total * 100) > self::HISTORIQUE_RATIO_REJETS;
    }

    private function determinerNiveau(int $score): string
    {
        if ($score >= self::SEUIL_ELEVE) {
            return Signalement::NIVEAU_ELEVE;
        }
        if ($score >= self::SEUIL_MOYEN) {
            return Signalement::NIVEAU_MOYEN;
        }

        return Signalement::NIVEAU_FAIBLE;
    }

    private function haversineM(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R    = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2
              + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}

