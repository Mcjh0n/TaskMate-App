<?php

namespace App\Http\Requests;

use App\Models\Agreement;
use Illuminate\Foundation\Http\FormRequest;

class StoreAgreementVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $agreement = $this->route('agreement');

        return $agreement instanceof Agreement && ($this->user()?->can('revise', $agreement) ?? false);
    }

    public function rules(): array
    {
        return [
            'version_id' => ['required', 'integer', 'exists:agreement_versions,id'],
            'deliverables' => ['required', 'string', 'max:10000'],
            'terms' => ['required', 'string', 'max:'.config('taskmate.agreement.terms_max_length', 3000)],
            'final_amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'start_date' => ['nullable', 'date', 'after:today'],
            'payment_arrangement' => ['required', 'string', 'max:2000'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
