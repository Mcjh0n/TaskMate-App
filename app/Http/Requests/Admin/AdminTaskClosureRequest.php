<?php

namespace App\Http\Requests\Admin;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;

class AdminTaskClosureRequest extends FormRequest
{
    public function authorize(): bool
    {
        $task = $this->route('task');
        return $this->user()?->isAdmin() && $task instanceof Task && $task->status === TaskStatus::InProgress && $task->agreement?->locked_at !== null;
    }
    public function rules(): array { return ['note' => ['required', 'string', 'min:5', 'max:3000']]; }
}
