<?php

namespace App\Http\Requests;

use App\Models\Task;
use App\Enums\ApplicationStatus;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('work_mode') === 'remote') {
            $this->merge(['workplace_name' => null, 'workplace_address' => null, 'workplace_setting' => null]);
        }
        if ($this->exists('hazard_exposure') && ! $this->boolean('hazard_exposure')) {
            $this->merge(['hazard_description' => null]);
        }
    }

    public function authorize(): bool
    {
        $task = $this->route('task');

        if ($task instanceof Task && $task->poster_id === $this->user()?->id
            && ($task->status === TaskStatus::Assigned
                || $task->applications()->whereIn('status', [ApplicationStatus::Pending->value, ApplicationStatus::Accepted->value])->exists())) {
            abort(403, 'This task has active applicants or is assigned. Cancel it and post a new task to change its details.');
        }

        return $task instanceof Task && ($this->user()?->can('update', $task) ?? false);
    }

    public function rules(): array
    {
        $requiresWorkplace = in_array($this->input('work_mode'), ['onsite', 'hybrid'], true);
        $physicalDemand = config('taskmate.risk_questions.physical_demand', []);
        $academicInvolvement = config('taskmate.risk_questions.academic_involvement', []);
        $workplaceSettings = config('taskmate.risk_questions.workplace_setting', []);

        return [
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', Rule::in(array_keys(config('taskmate.categories', [])))],
            'description' => ['required', 'string', 'max:10000'],
            'required_activities' => ['nullable', 'string', 'max:10000'],
            'deliverables' => ['nullable', 'string', 'max:10000'],
            'estimated_duration_hours' => ['nullable', 'numeric', 'min:0.5', 'max:500'],
            'skill_requirements' => ['nullable', 'string', 'max:5000'],
            'complexity' => ['nullable', 'string', Rule::in(array_keys(config('taskmate.complexity_levels', [])))],
            'budget' => ['required', 'numeric', 'min:1', 'max:999999.99'],
            'work_mode' => ['required', 'string', Rule::in(array_keys(config('taskmate.work_modes', [])))],
            'workplace_name' => [$requiresWorkplace ? 'required' : 'nullable', 'string', 'max:255'],
            'workplace_address' => [$requiresWorkplace ? 'required' : 'nullable', 'string', 'max:255'],
            'deadline' => ['nullable', 'date', 'after:today'],
            'guidelines_confirmed' => ['required', 'accepted'],
            'physical_demand' => ['nullable', 'string', Rule::in(array_keys($physicalDemand))],
            'academic_involvement' => ['nullable', 'string', Rule::in(array_keys($academicInvolvement))],
            'hazard_exposure' => ['nullable', 'boolean'],
            'hazard_description' => ['required_if:hazard_exposure,1,true', 'nullable', 'string', 'max:3000'],
            'private_meeting_required' => ['nullable', 'boolean'],
            'workplace_setting' => ['nullable', 'string', Rule::in(array_keys($workplaceSettings))],
        ];
    }

    public function messages(): array
    {
        return ['guidelines_confirmed.accepted' => 'You must confirm that this task follows the platform rules.'];
    }
}
