<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('work_mode') === 'remote') {
            $this->merge(['workplace_name' => null, 'workplace_address' => null]);
        }
    }

    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task && ($this->user()?->can('update', $task) ?? false);
    }

    public function rules(): array
    {
        $requiresWorkplace = in_array($this->input('work_mode'), ['onsite', 'hybrid'], true);

        return [
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', Rule::in(array_keys(config('taskmate.categories', [])))],
            'description' => ['required', 'string', 'max:10000'],
            'required_activities' => ['required', 'string', 'max:10000'],
            'deliverables' => ['required', 'string', 'max:10000'],
            'estimated_duration_hours' => ['required', 'numeric', 'min:0.5', 'max:500'],
            'skill_requirements' => ['nullable', 'string', 'max:5000'],
            'complexity' => ['required', 'string', Rule::in(array_keys(config('taskmate.complexity_levels', [])))],
            'budget' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'work_mode' => ['required', 'string', Rule::in(array_keys(config('taskmate.work_modes', [])))],
            'workplace_name' => [$requiresWorkplace ? 'required' : 'nullable', 'string', 'max:255'],
            'workplace_address' => [$requiresWorkplace ? 'required' : 'nullable', 'string', 'max:255'],
            'deadline' => ['nullable', 'date', 'after:today'],
            'guidelines_confirmed' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return ['guidelines_confirmed.accepted' => 'You must confirm that this task follows the platform rules.'];
    }
}
