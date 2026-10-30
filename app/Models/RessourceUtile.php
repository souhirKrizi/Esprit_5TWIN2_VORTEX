<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RessourceUtile extends Model
{
    use HasFactory;

    protected $table = 'ressources_utiles';

    protected $fillable = [
        'nom',
        'type',
        'gouvernorat',
        'adresse',
        'latitude',
        'longitude',
        'statut',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function signalements(): HasMany
    {
        return $this->hasMany(Signalement::class);
    }

    public function signalementsValides(): HasMany
    {
        return $this->signalements()->where('statut_validation', Signalement::STATUT_VALIDE);
    }

    public function getEtatAttribute(): string
    {
        return $this->signalements()
            ->where('type_signalement', Signalement::TYPE_INCIDENT)
            ->where('statut_validation', Signalement::STATUT_VALIDE)
            ->exists() ? 'rouge' : 'vert';
    }

    #[Scope]
    protected function actif(Builder $query): void
    {
        $query->where('statut', 'actif');
    }
}
