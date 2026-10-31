<?php

namespace App\Http\Requests\Prevention;

use App\Models\GesteCle;
use Illuminate\Validation\Rule;

/**
 * Gestes proposés par l'IA que l'administrateur a cochés : seuls ceux-là sont contrôlés et ajoutés.
 */
class AjoutGestesIaRequest extends PreventionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'gestes' => collect((array) $this->input('gestes', []))
                ->filter(fn ($geste) => is_array($geste) && ! empty($geste['choisi']))
                ->all(),
        ]);
    }

    public function rules(): array
    {
        return [
            'gestes' => ['required', 'array', 'max:12'],
            'gestes.*.intitule' => [
                'bail', 'required', 'string', 'min:5', 'max:150', 'distinct:ignore_case',
                Rule::unique('gestes_cles', 'intitule')->where('fiche_conseil_id', $this->route('fiche')->getKey()),
            ],
            'gestes.*.moment' => ['required', Rule::in(array_keys(GesteCle::MOMENTS))],
        ];
    }

    public function attributes(): array
    {
        return [
            'gestes.*.intitule' => 'geste',
            'gestes.*.moment' => 'moment',
        ];
    }

    protected function messagesPropres(): array
    {
        return [
            'gestes.required' => 'Cochez au moins un geste à ajouter.',
            'gestes.array' => 'La liste des gestes n’est pas valide.',
            'gestes.max' => 'Vous ne pouvez pas ajouter plus de 12 gestes à la fois.',
            'gestes.*.intitule.distinct' => 'Ce geste est présent deux fois dans votre sélection.',
            'gestes.*.intitule.unique' => 'Ce geste existe déjà dans cette fiche.',
        ];
    }

    protected function getRedirectUrl()
    {
        return route('admin.prevention.fiches.show', $this->route('fiche')).'#gestes-ia';
    }
}
