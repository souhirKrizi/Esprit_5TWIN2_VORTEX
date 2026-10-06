<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PieceJointe extends Model
{
    use HasFactory;

    protected $table = 'pieces_jointes';

    protected $fillable = [
        'signalement_id',
        'chemin_fichier',
        'nom_original',
        'type_mime',
        'taille',
    ];

    public function signalement(): BelongsTo
    {
        return $this->belongsTo(Signalement::class);
    }

    protected static function booted(): void
    {
        static::deleting(function (self $pieceJointe): void {
            Storage::disk('public')->delete($pieceJointe->chemin_fichier);
        });
    }
}
