<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionCitoyen extends Model
{
    protected $table = 'questions_citoyens';

    public const STATUTS = [
        'en_attente' => 'En attente',
        'repondue' => 'Répondue',
    ];

    protected $fillable = [
        'question',
        'reponse',
        'statut',
    ];

    public function fiche(): BelongsTo
    {
        return $this->belongsTo(FicheConseil::class, 'fiche_conseil_id');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function appartientA(User $user): bool
    {
        return (int) $this->user_id === (int) $user->getKey();
    }

    /** Le citoyen peut modifier ou supprimer sa question tant qu'elle n'a pas de réponse. */
    public function estModifiable(): bool
    {
        return $this->statut === 'en_attente';
    }

    public function libelleStatut(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }
}
