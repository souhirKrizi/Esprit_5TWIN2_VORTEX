<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mission extends Model
{
    public const STATUTS = ['ouverte', 'complete', 'en_cours', 'terminee', 'annulee'];

    protected $fillable = [
        'campagne_id', 'titre', 'description', 'lieu',
        'date_debut', 'date_fin', 'nombre_places', 'statut',
    ];

    protected function casts(): array
    {
        return [
            'date_debut' => 'datetime',
            'date_fin' => 'datetime',
            'nombre_places' => 'integer',
        ];
    }

    public function campagne(): BelongsTo
    {
        return $this->belongsTo(Campagne::class);
    }
}
