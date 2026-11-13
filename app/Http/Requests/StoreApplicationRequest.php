<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! $this->user()?->canApply()) {
            abort(403, 'Only verified students can apply to tasks.');
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:'.(int) config('taskmate.applications.message_max_length', 1000)],
            'proposed_amount' => ['nullable', 'numeric', 'min:1', 'max:1000000'],
            'work_details_confirmed' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return ['work_details_confirmed.accepted' => 'Confirm that you have read the work details before applying.'];
    }
}
