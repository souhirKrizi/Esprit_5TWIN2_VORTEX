<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campagne extends Model
{
    public const STATUTS = ['brouillon', 'publiee', 'en_cours', 'terminee', 'annulee'];

    protected $fillable = [
        'organisateur_id', 'titre', 'description', 'objectif', 'lieu',
        'date_debut', 'date_fin', 'statut', 'image',
    ];

    protected function casts(): array
    {
        return ['date_debut' => 'date:Y-m-d', 'date_fin' => 'date:Y-m-d'];
    }

    public function organisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organisateur_id');
    }
}
