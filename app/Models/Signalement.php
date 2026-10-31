<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Signalement extends Model
{
    use HasFactory;

    public const TYPE_INCIDENT = 'incident';
    public const TYPE_RESSOURCE_OK = 'ressource_ok';
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_VALIDE = 'valide';
    public const STATUT_REJETE = 'rejete';

    // Niveaux de suspicion
    public const NIVEAU_FAIBLE = 'faible';
    public const NIVEAU_MOYEN  = 'moyen';
    public const NIVEAU_ELEVE  = 'eleve';

    // Statuts d'analyse IA
    public const ANALYSE_NON_ANALYSE  = 'non_analyse';
    public const ANALYSE_ANALYSE      = 'analyse';
    public const ANALYSE_INDISPONIBLE = 'indisponible';

    protected $fillable = [
        'user_id',
        'ressource_utile_id',
        'type_signalement',
        'type_incident',
        'latitude',
        'longitude',
        'commentaire',
        'statut_validation',
        'motif_rejet',
        'score_suspicion',
        'niveau_suspicion',
        'motifs_suspicion',
        'doublon_de_id',
        'analyse_ia_statut',
        'analyse_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude'         => 'decimal:7',
            'longitude'        => 'decimal:7',
            'motifs_suspicion' => 'array',
            'analyse_at'       => 'datetime',
            'score_suspicion'  => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ressourceUtile(): BelongsTo
    {
        return $this->belongsTo(RessourceUtile::class);
    }

    public function piecesJointes(): HasMany
    {
        return $this->hasMany(PieceJointe::class);
    }

    /**
     * Signalement dont celui-ci est un doublon.
     */
    public function doublonDe(): BelongsTo
    {
        return $this->belongsTo(Signalement::class, 'doublon_de_id');
    }

    /**
     * Signalements identifiés comme doublons de celui-ci.
     */
    public function doublons(): HasMany
    {
        return $this->hasMany(Signalement::class, 'doublon_de_id');
    }

    #[Scope]
    protected function valide(Builder $query): void
    {
        $query->where('statut_validation', self::STATUT_VALIDE);
    }

    #[Scope]
    protected function incidentActif(Builder $query): void
    {
        $query->where('type_signalement', self::TYPE_INCIDENT)
            ->where('statut_validation', self::STATUT_VALIDE);
    }

    #[Scope]
    protected function deUtilisateur(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $signalement): void {
            $signalement->piecesJointes()->get()->each->delete();
        });
    }
}
