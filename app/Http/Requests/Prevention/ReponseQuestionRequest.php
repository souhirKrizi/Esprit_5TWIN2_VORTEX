<?php

namespace App\Http\Requests\Prevention;

class ReponseQuestionRequest extends PreventionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'reponse' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return ['reponse' => 'réponse'];
    }
}
