<?php

namespace App\Http\Requests;

use App\Models\Agreement;
use Illuminate\Foundation\Http\FormRequest;

class CancelAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agreement = $this->route('agreement');
        return $agreement instanceof Agreement && ($this->user()?->can('cancel', $agreement) ?? false);
    }

    public function rules(): array { return ['reason' => ['required', 'string', 'max:2000']]; }
}
