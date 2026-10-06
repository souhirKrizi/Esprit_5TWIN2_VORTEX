<?php

namespace App\Http\Requests\Prevention;

class QuestionCitoyenRequest extends PreventionRequest
{
    public function authorize(): bool
    {
        $question = $this->route('question');

        return $this->user() !== null && ($question === null || $question->appartientA($this->user()));
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['question' => 'question'];
    }
}
