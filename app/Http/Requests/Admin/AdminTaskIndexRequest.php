<?php

namespace App\Http\Requests\Admin;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminTaskIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(array_column(TaskStatus::cases(), 'value'))],
            'work_mode' => ['nullable', Rule::in(array_keys(config('taskmate.work_modes', [])))],
        ];
    }
}
