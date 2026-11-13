<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInitialAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');
        return $task instanceof Task && ($this->user()?->can('create', [\App\Models\Agreement::class, $task]) ?? false);
    }

    public function rules(): array
    {
        return [
            'deliverables' => ['required', 'string', 'max:10000'],
            'terms' => ['required', 'string', 'max:'.config('taskmate.agreement.terms_max_length', 3000)],
            'final_amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'start_date' => ['nullable', 'date', 'after:today'],
            'due_date' => ['required', 'date', 'after:today'],
            'payment_arrangement' => ['required', 'string', 'max:2000'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
