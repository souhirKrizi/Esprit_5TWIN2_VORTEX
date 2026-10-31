<?php

namespace App\Services;

use App\Models\RessourceUtile;
use App\Models\Signalement;
use Illuminate\Support\Collection;

class ExportSignalementsService
{
    /** Caractères d'injection de formules CSV. */
    private const FORMULE_PREFIXES = ['=', '+', '-', '@', "\t", "\r", "\n"];

    /**
     * Neutralise une cellule pour éviter l'injection de formule dans Excel/Calc.
     * Préfixe d'une apostrophe si la valeur commence par un caractère dangereux.
     */
    public function neutraliserCellule(string $valeur): string
    {
        if ($valeur === '') {
            return '';
        }
        if (in_array(mb_substr($valeur, 0, 1), self::FORMULE_PREFIXES, true)) {
            return "'" . $valeur;
        }
        return $valeur;
    }

    /**
     * Libellés français centralisés.
     */
    public function libelles(): array
    {
        return [
            // Statuts
            Signalement::STATUT_EN_ATTENTE => 'En attente',
            Signalement::STATUT_VALIDE     => 'Validé',
            Signalement::STATUT_REJETE     => 'Rejeté',
            // Types signalement
            'incident'     => 'Incident',
            'ressource_ok' => 'Ressource OK',
            // Types incident
            'coupure_electricite'    => 'Coupure électricité',
            'point_eau_hors_service' => 'Point eau hors service',
            'centre_ferme'           => 'Centre fermé',
            'autre'                  => 'Autre',
            // Niveaux suspicion
            'faible' => 'Faible',
            'moyen'  => 'Moyen',
            'eleve'  => 'Élevé',
            // Analyse IA statuts
            'non_analyse'  => 'Non analysé',
            'analyse'      => 'Analysé',
            'indisponible' => 'Indisponible',
        ];
    }

    /**
     * Résumé par gouvernorat (Partie A).
     * Aucune requête N+1 : tout en SQL aggregat.
     */
    public function resumeParGouvernorat(array $filtres): Collection
    {
        $lib = $this->libelles();

        // Agréger les ressources_utiles avec statistiques signalements associés
        $lignesRessources = RessourceUtile::query()
            ->selectRaw('gouvernorat')
            ->selectRaw('COUNT(DISTINCT ressources_utiles.id) as nb_ressources')
            ->selectRaw(
                'SUM(CASE WHEN EXISTS(
                    SELECT 1 FROM signalements si
                    WHERE si.ressource_utile_id = ressources_utiles.id
                      AND si.type_signalement = ?
                      AND si.statut_validation = ?
                ) THEN 1 ELSE 0 END) as ressources_rouges',
                [Signalement::TYPE_INCIDENT, Signalement::STATUT_VALIDE]
            )
            ->selectRaw(
                'SUM(CASE WHEN NOT EXISTS(
                    SELECT 1 FROM signalements si
                    WHERE si.ressource_utile_id = ressources_utiles.id
                      AND si.type_signalement = ?
                      AND si.statut_validation = ?
                ) THEN 1 ELSE 0 END) as ressources_vertes',
                [Signalement::TYPE_INCIDENT, Signalement::STATUT_VALIDE]
            )
            ->groupBy('gouvernorat')
            ->orderBy('gouvernorat')
            ->get()
            ->keyBy('gouvernorat');

        // Agréger les signalements par gouvernorat (via ressource_utile_id)
        $statsSignalements = Signalement::query()
            ->join('ressources_utiles', 'signalements.ressource_utile_id', '=', 'ressources_utiles.id')
            ->selectRaw('ressources_utiles.gouvernorat')
            ->selectRaw('COUNT(signalements.id) as total_signalements')
            ->selectRaw('SUM(CASE WHEN signalements.statut_validation = ? THEN 1 ELSE 0 END) as en_attente', [Signalement::STATUT_EN_ATTENTE])
            ->selectRaw('SUM(CASE WHEN signalements.statut_validation = ? THEN 1 ELSE 0 END) as valides', [Signalement::STATUT_VALIDE])
            ->selectRaw('SUM(CASE WHEN signalements.statut_validation = ? THEN 1 ELSE 0 END) as rejetes', [Signalement::STATUT_REJETE])
            ->selectRaw('SUM(CASE WHEN signalements.type_signalement = ? AND signalements.statut_validation = ? THEN 1 ELSE 0 END) as incidents_valides', [Signalement::TYPE_INCIDENT, Signalement::STATUT_VALIDE])
            ->selectRaw('SUM(CASE WHEN signalements.type_signalement = ? AND signalements.type_incident = ? AND signalements.statut_validation = ? THEN 1 ELSE 0 END) as coupure_electricite', [Signalement::TYPE_INCIDENT, 'coupure_electricite', Signalement::STATUT_VALIDE])
            ->selectRaw('SUM(CASE WHEN signalements.type_signalement = ? AND signalements.type_incident = ? AND signalements.statut_validation = ? THEN 1 ELSE 0 END) as point_eau_hors_service', [Signalement::TYPE_INCIDENT, 'point_eau_hors_service', Signalement::STATUT_VALIDE])
            ->selectRaw('SUM(CASE WHEN signalements.type_signalement = ? AND signalements.type_incident = ? AND signalements.statut_validation = ? THEN 1 ELSE 0 END) as centre_ferme', [Signalement::TYPE_INCIDENT, 'centre_ferme', Signalement::STATUT_VALIDE])
            ->selectRaw('SUM(CASE WHEN signalements.type_signalement = ? AND signalements.type_incident = ? AND signalements.statut_validation = ? THEN 1 ELSE 0 END) as autre', [Signalement::TYPE_INCIDENT, 'autre', Signalement::STATUT_VALIDE])
            ->selectRaw(
                'SUM(CASE WHEN signalements.type_signalement = ? AND signalements.statut_validation = ? THEN 1 ELSE 0 END) as ressources_ok_valides',
                [Signalement::TYPE_RESSOURCE_OK, Signalement::STATUT_VALIDE]
            )
            ->selectRaw('MAX(signalements.created_at) as dernier_signalement')
            ->groupBy('ressources_utiles.gouvernorat')
            ->get()
            ->keyBy('gouvernorat');

        // Construire les lignes : tous les gouvernorats de ressources_utiles (même 0 signalements)
        $lignes = $lignesRessources->map(function ($ressRow) use ($statsSignalements) {
            $stats = $statsSignalements->get($ressRow->gouvernorat);
            return [
                'gouvernorat'            => $ressRow->gouvernorat,
                'nb_ressources'          => (int) $ressRow->nb_ressources,
                'ressources_rouges'      => (int) $ressRow->ressources_rouges,
                'ressources_vertes'      => (int) $ressRow->ressources_vertes,
                'total_signalements'     => $stats ? (int) $stats->total_signalements : 0,
                'en_attente'             => $stats ? (int) $stats->en_attente : 0,
                'valides'                => $stats ? (int) $stats->valides : 0,
                'rejetes'                => $stats ? (int) $stats->rejetes : 0,
                'incidents_valides'      => $stats ? (int) $stats->incidents_valides : 0,
                'coupure_electricite'    => $stats ? (int) $stats->coupure_electricite : 0,
                'point_eau_hors_service' => $stats ? (int) $stats->point_eau_hors_service : 0,
                'centre_ferme'           => $stats ? (int) $stats->centre_ferme : 0,
                'autre'                  => $stats ? (int) $stats->autre : 0,
                'ressources_ok_valides'  => $stats ? (int) $stats->ressources_ok_valides : 0,
                'dernier_signalement'    => $stats?->dernier_signalement
                    ? \Carbon\Carbon::parse($stats->dernier_signalement)->format('d/m/Y H:i')
                    : '',
            ];
        });

        // Ligne "Non rattaché" (signalements sans ressource_utile_id)
        $nonRattache = Signalement::query()
            ->whereNull('ressource_utile_id')
            ->selectRaw('COUNT(id) as total_signalements')
            ->selectRaw('SUM(CASE WHEN statut_validation = ? THEN 1 ELSE 0 END) as en_attente', [Signalement::STATUT_EN_ATTENTE])
            ->selectRaw('SUM(CASE WHEN statut_validation = ? THEN 1 ELSE 0 END) as valides', [Signalement::STATUT_VALIDE])
            ->selectRaw('SUM(CASE WHEN statut_validation = ? THEN 1 ELSE 0 END) as rejetes', [Signalement::STATUT_REJETE])
            ->selectRaw('SUM(CASE WHEN type_signalement = ? AND statut_validation = ? THEN 1 ELSE 0 END) as incidents_valides', [Signalement::TYPE_INCIDENT, Signalement::STATUT_VALIDE])
            ->selectRaw('SUM(CASE WHEN type_signalement = ? AND type_incident = ? AND statut_validation = ? THEN 1 ELSE 0 END) as coupure_electricite', [Signalement::TYPE_INCIDENT, 'coupure_electricite', Signalement::STATUT_VALIDE])
            ->selectRaw('SUM(CASE WHEN type_signalement = ? AND type_incident = ? AND statut_validation = ? THEN 1 ELSE 0 END) as point_eau_hors_service', [Signalement::TYPE_INCIDENT, 'point_eau_hors_service', Signalement::STATUT_VALIDE])
            ->selectRaw('SUM(CASE WHEN type_signalement = ? AND type_incident = ? AND statut_validation = ? THEN 1 ELSE 0 END) as centre_ferme', [Signalement::TYPE_INCIDENT, 'centre_ferme', Signalement::STATUT_VALIDE])
            ->selectRaw('SUM(CASE WHEN type_signalement = ? AND type_incident = ? AND statut_validation = ? THEN 1 ELSE 0 END) as autre', [Signalement::TYPE_INCIDENT, 'autre', Signalement::STATUT_VALIDE])
            ->selectRaw('SUM(CASE WHEN type_signalement = ? AND statut_validation = ? THEN 1 ELSE 0 END) as ressources_ok_valides', [Signalement::TYPE_RESSOURCE_OK, Signalement::STATUT_VALIDE])
            ->selectRaw('MAX(created_at) as dernier_signalement')
            ->first();

        $lignes->put('__non_rattache__', [
            'gouvernorat'            => 'Non rattaché',
            'nb_ressources'          => 0,
            'ressources_rouges'      => 0,
            'ressources_vertes'      => 0,
            'total_signalements'     => (int) ($nonRattache?->total_signalements ?? 0),
            'en_attente'             => (int) ($nonRattache?->en_attente ?? 0),
            'valides'                => (int) ($nonRattache?->valides ?? 0),
            'rejetes'                => (int) ($nonRattache?->rejetes ?? 0),
            'incidents_valides'      => (int) ($nonRattache?->incidents_valides ?? 0),
            'coupure_electricite'    => (int) ($nonRattache?->coupure_electricite ?? 0),
            'point_eau_hors_service' => (int) ($nonRattache?->point_eau_hors_service ?? 0),
            'centre_ferme'           => (int) ($nonRattache?->centre_ferme ?? 0),
            'autre'                  => (int) ($nonRattache?->autre ?? 0),
            'ressources_ok_valides'  => (int) ($nonRattache?->ressources_ok_valides ?? 0),
            'dernier_signalement'    => $nonRattache?->dernier_signalement
                ? \Carbon\Carbon::parse($nonRattache->dernier_signalement)->format('d/m/Y H:i')
                : '',
        ]);

        // Ligne TOTAL
        $total = [
            'gouvernorat'            => 'TOTAL',
            'nb_ressources'          => $lignes->sum('nb_ressources'),
            'ressources_rouges'      => $lignes->sum('ressources_rouges'),
            'ressources_vertes'      => $lignes->sum('ressources_vertes'),
            'total_signalements'     => $lignes->sum('total_signalements'),
            'en_attente'             => $lignes->sum('en_attente'),
            'valides'                => $lignes->sum('valides'),
            'rejetes'                => $lignes->sum('rejetes'),
            'incidents_valides'      => $lignes->sum('incidents_valides'),
            'coupure_electricite'    => $lignes->sum('coupure_electricite'),
            'point_eau_hors_service' => $lignes->sum('point_eau_hors_service'),
            'centre_ferme'           => $lignes->sum('centre_ferme'),
            'autre'                  => $lignes->sum('autre'),
            'ressources_ok_valides'  => $lignes->sum('ressources_ok_valides'),
            'dernier_signalement'    => '',
        ];
        $lignes->put('__total__', $total);

        return $lignes;
    }

    /**
     * Query filtrée pour la Partie B (détail des signalements).
     */
    public function detailQuery(array $filtres)
    {
        return Signalement::query()
            ->with(['user:id,name,email', 'ressourceUtile:id,nom,type,gouvernorat'])
            ->withCount('piecesJointes')
            ->when(
                ! empty($filtres['statut']),
                fn ($q) => $q->where('statut_validation', $filtres['statut'])
            )
            ->when(
                ! empty($filtres['type']),
                fn ($q) => $q->where('type_signalement', $filtres['type'])
            )
            ->when(
                ! empty($filtres['search']),
                function ($q) use ($filtres): void {
                    $search = $filtres['search'];
                    $q->where(function ($q) use ($search): void {
                        $q->where('commentaire', 'like', "%{$search}%")
                            ->orWhere('type_incident', 'like', "%{$search}%")
                            ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                    });
                }
            )
            ->oldest();
    }

    /**
     * Écrit le fichier CSV complet dans le handle ouvert (php://output).
     * BOM UTF-8, séparateur ";", CRLF.
     */
    public function ecrireCsv($handle, array $filtres): void
    {
        $lib = $this->libelles();

        // BOM UTF-8
        fwrite($handle, "\xEF\xBB\xBF");

        // ── PARTIE A ────────────────────────────────────────────────────────
        $this->ecrireLigne($handle, ['RÉSUMÉ PAR GOUVERNORAT']);
        $this->ecrireLigne($handle, [
            'Gouvernorat',
            'Nb ressources',
            'Ressources rouges',
            'Ressources vertes',
            'Total signalements',
            'En attente',
            'Validés',
            'Rejetés',
            'Incidents validés',
            'Dont coupure_electricite',
            'Dont point_eau_hors_service',
            'Dont centre_ferme',
            'Dont autre',
            'Ressources OK validées',
            'Dernier signalement (date)',
        ]);

        foreach ($this->resumeParGouvernorat($filtres) as $row) {
            $this->ecrireLigne($handle, [
                $this->neutraliserCellule((string) $row['gouvernorat']),
                $row['nb_ressources'],
                $row['ressources_rouges'],
                $row['ressources_vertes'],
                $row['total_signalements'],
                $row['en_attente'],
                $row['valides'],
                $row['rejetes'],
                $row['incidents_valides'],
                $row['coupure_electricite'],
                $row['point_eau_hors_service'],
                $row['centre_ferme'],
                $row['autre'],
                $row['ressources_ok_valides'],
                $row['dernier_signalement'],
            ]);
        }

        // Ligne vide de séparation
        $this->ecrireLigne($handle, ['']);

        // ── PARTIE B ────────────────────────────────────────────────────────
        $this->ecrireLigne($handle, ['DÉTAIL DES SIGNALEMENTS']);
        $this->ecrireLigne($handle, [
            'ID',
            'Date création',
            'Citoyen (nom)',
            'Citoyen (email)',
            'Type signalement',
            'Type incident',
            'Gouvernorat ressource',
            'Ressource',
            'Latitude',
            'Longitude',
            'Commentaire',
            'Statut',
            'Motif rejet',
            'Nb photos',
            'Score suspicion',
            'Niveau suspicion',
        ]);

        $this->detailQuery($filtres)->chunkById(500, function ($signalements) use ($handle, $lib): void {
            foreach ($signalements as $s) {
                $this->ecrireLigne($handle, [
                    $s->id,
                    $s->created_at?->format('d/m/Y H:i') ?? '',
                    $this->neutraliserCellule($s->user?->name ?? ''),
                    $this->neutraliserCellule($s->user?->email ?? ''),
                    $lib[$s->type_signalement] ?? $s->type_signalement,
                    $lib[$s->type_incident] ?? ($s->type_incident ?? ''),
                    $this->neutraliserCellule($s->ressourceUtile?->gouvernorat ?? 'Non rattaché'),
                    $this->neutraliserCellule($s->ressourceUtile?->nom ?? ''),
                    $s->latitude ?? '',
                    $s->longitude ?? '',
                    $this->neutraliserCellule($s->commentaire ?? ''),
                    $lib[$s->statut_validation] ?? $s->statut_validation,
                    $this->neutraliserCellule($s->motif_rejet ?? ''),
                    $s->pieces_jointes_count ?? 0,
                    $s->score_suspicion ?? '',
                    $lib[$s->niveau_suspicion] ?? ($s->niveau_suspicion ?? ''),
                ]);
            }
        });
    }

    /**
     * Écrit une ligne CSV avec séparateur ";" et fin de ligne \r\n.
     */
    private function ecrireLigne($handle, array $colonnes): void
    {
        fputcsv($handle, array_map('strval', $colonnes), ';', '"', '\\', "\r\n");
    }
}

