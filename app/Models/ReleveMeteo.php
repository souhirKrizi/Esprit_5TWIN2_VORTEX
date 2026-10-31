<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReleveMeteo extends Model
{
    public const TYPES = ['observation' => 'Observation', 'prevision' => 'Prévision'];

    protected $table = 'releves_meteo';

    protected $fillable = [
        'zone_id', 'type', 'date_mesure', 'temperature', 'humidite', 'vitesse_vent', 'indice_uv', 'source',
    ];

    protected function casts(): array
    {
        return [
            'date_mesure' => 'datetime',
            'temperature' => 'decimal:2',
            'humidite' => 'integer',
            'vitesse_vent' => 'decimal:2',
            'indice_uv' => 'decimal:1',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }
}