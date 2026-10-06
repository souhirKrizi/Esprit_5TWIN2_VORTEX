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
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
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
