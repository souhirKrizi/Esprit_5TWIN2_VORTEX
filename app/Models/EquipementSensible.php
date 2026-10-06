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
        return ['autonomie_heures' => 'integer'];
    }

    public function appartientA(User $user): bool
    {
        return (int) $this->user_id === (int) $user->getKey();
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
