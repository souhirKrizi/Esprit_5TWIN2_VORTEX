<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GesteCle extends Model
{
    protected $table = 'gestes_cles';

    public const MOMENTS = [
        'avant' => 'Avant',
        'pendant' => 'Pendant',
        'apres' => 'Après',
    ];

    protected $fillable = [
        'intitule',
        'moment',
        'ordre',
    ];

    protected function casts(): array
    {
        return ['ordre' => 'integer'];
    }

    public function fiche(): BelongsTo
    {
        return $this->belongsTo(FicheConseil::class, 'fiche_conseil_id');
    }

    public function libelleMoment(): string
    {
        return self::MOMENTS[$this->moment] ?? $this->moment;
    }
}
