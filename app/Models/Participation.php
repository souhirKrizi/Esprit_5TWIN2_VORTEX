<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participation extends Model
{
    public const ACTIVE_STATUTS = ['en_attente', 'acceptee'];

    protected $fillable = ['mission_id', 'user_id', 'motivation', 'statut', 'presence'];

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
