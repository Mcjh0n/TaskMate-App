<?php

namespace App\Http\Requests;

use App\Models\Agreement;
use Illuminate\Foundation\Http\FormRequest;

class StoreRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agreement = $this->route('agreement');
        if (! $agreement instanceof Agreement) {
            return false;
        }

        return $this->user()?->can('rate', $agreement) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'overall_experience' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:'.(int) config('taskmate.ratings.comment_max_length', 1000)],
        ];
    }
}
