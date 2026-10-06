<?php

namespace App\Http\Requests\Prevention;

use App\Models\GesteCle;
use Illuminate\Validation\Rule;

class GesteCleRequest extends PreventionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        $geste = $this->route('geste');
        $ficheId = $this->route('fiche')?->getKey() ?? $geste?->fiche_conseil_id;

        return [
            'intitule' => ['required', 'string', 'min:5', 'max:150', Rule::unique('gestes_cles', 'intitule')->where('fiche_conseil_id', $ficheId)->ignore($geste)],
            'moment' => ['required', Rule::in(array_keys(GesteCle::MOMENTS))],
            'ordre' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return [
            'intitule' => 'geste',
            'moment' => 'moment',
            'ordre' => 'ordre',
        ];
    }

    protected function messagesPropres(): array
    {
        return [
            'intitule.unique' => 'Ce geste existe déjà dans cette fiche.',
        ];
    }
}
