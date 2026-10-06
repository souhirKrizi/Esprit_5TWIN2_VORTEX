<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FicheConseil extends Model
{
    protected $table = 'fiches_conseils';

    public const THEMATIQUES = [
        'hydratation' => 'Hydratation',
        'economie_energie' => 'Économie d’énergie',
        'coupure_courant' => 'Coupure de courant',
        'equipements' => 'Équipements sensibles',
        'logement' => 'Fraîcheur du logement',
        'personnes_fragiles' => 'Personnes fragiles',
    ];

    public const NIVEAUX = [
        'vert' => 'Vert',
        'jaune' => 'Jaune',
        'orange' => 'Orange',
        'rouge' => 'Rouge',
    ];

    public const STATUTS = [
        'brouillon' => 'Brouillon',
        'publiee' => 'Publiée',
    ];

    protected $fillable = [
        'titre',
        'resume',
        'contenu',
        'thematique',
        'niveau_alerte',
        'type_equipement',
        'statut',
    ];

    public function gestes(): HasMany
    {
        return $this->hasMany(GesteCle::class)->orderBy('ordre')->orderBy('id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuestionCitoyen::class);
    }

    public function scopePubliees(Builder $query): Builder
    {
        return $query->where('statut', 'publiee');
    }

    public function estPubliee(): bool
    {
        return $this->statut === 'publiee';
    }

    public function libelleThematique(): string
    {
        return self::THEMATIQUES[$this->thematique] ?? $this->thematique;
    }

    public function libelleNiveau(): string
    {
        return self::NIVEAUX[$this->niveau_alerte] ?? $this->niveau_alerte;
    }

    public function libelleStatut(): string
    {
        return self::STATUTS[$this->statut] ?? $this->statut;
    }

    public function libelleEquipement(): ?string
    {
        return $this->type_equipement ? EquipementSensible::TYPES[$this->type_equipement] : null;
    }
}
