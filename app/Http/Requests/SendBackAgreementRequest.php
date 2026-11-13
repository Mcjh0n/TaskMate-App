<?php

namespace App\Http\Requests;

use App\Models\Agreement;
use Illuminate\Foundation\Http\FormRequest;

class SendBackAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agreement = $this->route('agreement');
        return $agreement instanceof Agreement && ($this->user()?->can('sendBack', $agreement) ?? false);
    }

    public function rules(): array
    {
        return ['version_id' => ['required', 'integer', 'exists:agreement_versions,id'], 'body' => ['required', 'string', 'max:1000']];
    }
}
