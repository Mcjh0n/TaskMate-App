<?php

namespace App\Http\Requests;

use App\Models\Agreement;
use Illuminate\Foundation\Http\FormRequest;

class ConfirmReceivedRequest extends FormRequest
{
    public function authorize(): bool { $agreement = $this->route('agreement'); return $agreement instanceof Agreement && ($this->user()?->can('respondPayment', $agreement) ?? false); }
    public function rules(): array { return ['amount' => ['nullable', 'numeric', 'min:1', 'max:1000000']]; }
}
