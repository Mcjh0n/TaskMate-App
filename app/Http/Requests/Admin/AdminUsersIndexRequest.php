<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserType;
use App\Enums\VerificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUsersIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'user_type' => ['nullable', Rule::enum(UserType::class)],
            'verification_status' => ['nullable', Rule::enum(VerificationStatus::class)],
        ];
    }
}
