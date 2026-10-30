<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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
        'image',
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

    /**
     * Niveau d'alerte en cours. Quand le module d'alertes existera, seule cette méthode sera à changer.
     */
    public static function niveauAlerteEnCours(): ?string
    {
        return DB::table('alerte_en_cours')->value('niveau');
    }

    public static function definirNiveauAlerteEnCours(?string $niveau): void
    {
        DB::table('alerte_en_cours')->updateOrInsert(['id' => 1], ['niveau' => $niveau, 'updated_at' => now()]);
    }

    /**
     * Conseils à lire en priorité : ceux du niveau en cours d'abord, puis ceux des niveaux inférieurs,
     * qui restent valables quand l'alerte monte.
     */
    public static function conseilsPourAlerte(string $niveau, int $limite = 3): Collection
    {
        $niveaux = array_keys(self::NIVEAUX);
        $position = array_search($niveau, $niveaux, true);

        return self::publiees()
            ->whereIn('niveau_alerte', array_slice($niveaux, 0, $position + 1))
            ->latest()
            ->latest('id')
            ->get()
            ->sortByDesc(fn (FicheConseil $fiche) => array_search($fiche->niveau_alerte, $niveaux, true))
            ->take($limite)
            ->values();
    }

    public function estPubliee(): bool
    {
        return $this->statut === 'publiee';
    }

    public function imageUrl(): ?string
    {
        return $this->image ? asset('storage/'.$this->image) : null;
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
