<?php

namespace App\Http\Requests\Admin;

use App\Models\Rating;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModerateRatingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $rating = $this->route('rating');
        return $rating instanceof Rating && $this->user()?->can('moderate', $rating);
    }

    public function rules(): array
    {
        return ['action' => ['required', Rule::in(['hide', 'restore'])], 'reason' => ['required_if:action,hide', 'nullable', 'string', 'max:2000']];
    }
}
