<?php

namespace App\Http\Requests;

use App\Models\Agreement;
use Illuminate\Foundation\Http\FormRequest;

class RecordCompletionRequest extends FormRequest
{
    public function authorize(): bool { $agreement = $this->route('agreement'); return $agreement instanceof Agreement && ($this->user()?->can('recordCompletion', $agreement) ?? false); }
    public function rules(): array { return ['note' => ['nullable', 'string', 'max:2000']]; }
}
