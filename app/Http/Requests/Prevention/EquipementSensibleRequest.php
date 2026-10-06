<?php

namespace App\Http\Requests\Prevention;

use App\Models\EquipementSensible;
use Illuminate\Validation\Rule;

class EquipementSensibleRequest extends PreventionRequest
{
    public function authorize(): bool
    {
        $equipement = $this->route('equipement');

        return $this->user() !== null && ($equipement === null || $equipement->appartientA($this->user()));
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'min:2', 'max:80', Rule::unique('equipements_sensibles', 'nom')->where('user_id', $this->user()->getKey())->ignore($this->route('equipement'))],
            'type' => ['required', Rule::in(array_keys(EquipementSensible::TYPES))],
            'sensibilite' => ['required', Rule::in(array_keys(EquipementSensible::SENSIBILITES))],
            'autonomie_heures' => ['nullable', 'integer', 'min:0', 'max:72'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nom' => 'nom',
            'type' => 'type',
            'sensibilite' => 'sensible à',
            'autonomie_heures' => 'autonomie',
        ];
    }

    protected function messagesPropres(): array
    {
        return [
            'nom.unique' => 'Vous avez déjà un équipement avec ce nom.',
            'autonomie_heures.min' => 'L’autonomie doit être comprise entre 0 et 72 heures.',
            'autonomie_heures.max' => 'L’autonomie doit être comprise entre 0 et 72 heures.',
        ];
    }
}
