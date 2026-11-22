<?php

namespace App\Http\Requests\Admin;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminTaskDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $admin = $this->user();
        $task = $this->route('task');

        return $admin?->isAdmin() === true
            && $task instanceof Task
            && $task->status === TaskStatus::PendingReview
            && $task->poster_id !== $admin->id;
    }

    public function rules(): array
    {
        $noteRequired = in_array($this->input('decision'), ['reject', 'request_changes'], true);

        return [
            'decision' => ['required', Rule::in(['approve', 'reject', 'request_changes'])],
            'note' => [$noteRequired ? 'required' : 'nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return ['note.required' => 'Add a note explaining this decision.'];
    }
}
