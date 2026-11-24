<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentFlagStatus;
use App\Models\ContentFlag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminFlagDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() && $this->route('flag') instanceof ContentFlag && $this->route('flag')->status === ContentFlagStatus::Open;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['decision' => ['required', Rule::in(['reviewed', 'dismissed'])], 'note' => ['required', 'string', 'min:3', 'max:2000']];
    }
}
