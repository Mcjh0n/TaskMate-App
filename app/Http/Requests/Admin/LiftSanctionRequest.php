<?php

namespace App\Http\Requests\Admin;

use App\Models\UserSanction;
use Illuminate\Foundation\Http\FormRequest;

class LiftSanctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $sanction = $this->route('sanction');

        return $this->user()?->isAdmin() && $sanction instanceof UserSanction && $sanction->user_id !== $this->user()->id && ! $sanction->user->isAdmin() && ! $sanction->lifted_at;
    }

    public function rules(): array
    {
        return ['note' => ['required', 'string', 'min:3', 'max:2000']];
    }
}
