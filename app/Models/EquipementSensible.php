<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipementSensible extends Model
{
    protected $table = 'equipements_sensibles';

    public const TYPES = [
        'refrigerateur' => 'Réfrigérateur / congélateur',
        'appareil_medical' => 'Appareil médical',
        'climatiseur' => 'Climatiseur / ventilateur',
        'internet_telephone' => 'Box internet / téléphone',
        'informatique' => 'Ordinateur / télévision',
    ];

    public const SENSIBILITES = [
        'chaleur' => 'La chaleur',
        'coupure' => 'Les coupures de courant',
        'les_deux' => 'Les deux',
    ];

    protected $fillable = [
        'nom',
        'type',
        'sensibilite',
        'autonomie_heures',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'autonomie_heures' => 'integer',
            'conseils_ia' => 'array',
            'conseils_ia_le' => 'datetime',
        ];
    }

    public function appartientA(User $user): bool
    {
        return (int) $this->user_id === (int) $user->getKey();
    }

    /**
     * Repère sans appeler l'IA les textes visiblement vides de sens (« aaaaaa », « sdfghjkl », « 123456 »).
     */
    public function descriptionSignificative(): bool
    {
        $description = mb_strtolower(trim((string) $this->description));

        if ($description === '') {
            return true;
        }

        $lettres = preg_replace('/[^\p{L}]/u', '', $description);
        $latines = preg_match_all('/\p{Latin}/u', $lettres);
        $voyelles = preg_match_all('/[aeiouyàâäéèêëîïôöùûüÿœ]/u', $lettres);

        return ! preg_match('/(\p{L})\1{3,}/u', $description)
            && count(array_unique(mb_str_split($lettres))) >= 5
            && ($latines === 0 || $voyelles / $latines >= 0.2);
    }

    public function libelleType(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function libelleSensibilite(): string
    {
        return self::SENSIBILITES[$this->sensibilite] ?? $this->sensibilite;
    }
}
