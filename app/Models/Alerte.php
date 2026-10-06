<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alerte extends Model
{
    public const NIVEAUX = ['jaune' => 'Vigilance', 'orange' => 'Alerte', 'rouge' => 'Alerte extrême'];

    public const STATUTS = ['brouillon' => 'Brouillon', 'active' => 'Active', 'terminee' => 'Terminée', 'annulee' => 'Annulée'];

    protected $fillable = [
        'zone_id', 'created_by', 'titre', 'description', 'niveau_gravite',
        'debut_validite', 'fin_validite', 'statut', 'origine',
    ];

    protected function casts(): array
    {
        return ['debut_validite' => 'datetime', 'fin_validite' => 'datetime'];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}