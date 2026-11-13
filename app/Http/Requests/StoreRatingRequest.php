<?php

namespace App\Http\Requests;

use App\Models\Agreement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agreement = $this->route('agreement');
        return $agreement instanceof Agreement && $this->user()?->can('rate', $agreement);
    }

    public function rules(): array
    {
        $agreement = $this->route('agreement');
        $direction = $agreement->poster_id === $this->user()->id ? 'poster_to_student' : 'student_to_poster';
        $dimensions = array_keys(config("taskmate.ratings.dimensions.{$direction}", []));
        $rules = [
            'overall_experience' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:'.(int) config('taskmate.ratings.comment_max_length', 1000)],
            'scores' => ['required', 'array', 'size:'.count($dimensions)],
        ];
        foreach ($dimensions as $dimension) $rules["scores.{$dimension}"] = ['required', 'integer', 'between:1,5'];
        $rules['scores.*'] = ['required', 'integer', 'between:1,5'];
        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $agreement = $this->route('agreement');
            if (! $agreement instanceof Agreement) return;
            $direction = $agreement->poster_id === $this->user()->id ? 'poster_to_student' : 'student_to_poster';
            $allowed = array_keys(config("taskmate.ratings.dimensions.{$direction}", []));
            $provided = array_keys((array) $this->input('scores', []));
            if (array_diff($provided, $allowed)) $validator->errors()->add('scores', 'Unknown rating dimensions were provided.');
        });
    }
}
