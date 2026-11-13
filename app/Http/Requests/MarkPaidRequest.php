<?php

namespace App\Http\Requests;

use App\Models\Agreement;
use Illuminate\Foundation\Http\FormRequest;

class MarkPaidRequest extends FormRequest
{
    public function authorize(): bool { $agreement = $this->route('agreement'); return $agreement instanceof Agreement && ($this->user()?->can('markPaid', $agreement) ?? false); }
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'paid_on' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
