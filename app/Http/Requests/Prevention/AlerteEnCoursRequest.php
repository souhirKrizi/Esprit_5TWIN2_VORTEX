<?php

namespace App\Http\Requests\Prevention;

use App\Models\FicheConseil;
use Illuminate\Validation\Rule;

class AlerteEnCoursRequest extends PreventionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'niveau' => ['nullable', Rule::in(array_keys(FicheConseil::NIVEAUX))],
        ];
    }

    public function attributes(): array
    {
        return ['niveau' => 'alerte en cours'];
    }
}
