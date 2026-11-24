<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerificationDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $admin = $this->user();
        $subject = $this->route('user');

        return $admin?->isAdmin() === true
            && $subject instanceof User
            && $admin->isNot($subject);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['verified', 'rejected', 'more_info_requested'])],
            'note' => ['required_if:decision,rejected,more_info_requested', 'prohibited_if:decision,verified', 'nullable', 'string', 'max:2000'],
        ];
    }
}
