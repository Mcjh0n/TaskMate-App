<?php

namespace App\Http\Requests;

use App\Enums\ApplicationStatus;
use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('work_mode') === 'online') {
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

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $durationLimits = config('taskmate.duration_limits');
        $unit = $this->input('estimated_duration_unit');
        $maximum = is_string($unit) ? ($durationLimits[$unit] ?? null) : null;

        $requiresWorkplace = in_array($this->input('work_mode'), ['on_campus', 'off_campus'], true);
        $physicalDemand = config('taskmate.risk_questions.physical_demand', []);
        $academicInvolvement = config('taskmate.risk_questions.academic_involvement', []);
        $workplaceSettings = config('taskmate.risk_questions.workplace_setting', []);

        return [
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', Rule::in(array_keys(config('taskmate.categories', [])))],
            'description' => ['required', 'string', 'max:10000'],
            'required_activities' => ['nullable', 'string', 'max:10000'],
            'deliverables' => ['nullable', 'string', 'max:10000'],
            'estimated_duration_value' => array_filter(['required', 'integer', 'min:1', $maximum === null ? null : 'max:'.$maximum]),
            'estimated_duration_unit' => ['required', 'string', Rule::in(array_keys($durationLimits))],
            'skill_requirements' => ['nullable', 'string', 'max:5000'],
            'budget_mode' => ['required', Rule::in(['single', 'range'])],
            'budget' => ['exclude_unless:budget_mode,single', 'required', 'numeric', 'decimal:0,2', 'min:1', 'max:999999.99'],
            'budget_min' => ['exclude_unless:budget_mode,range', 'required', 'numeric', 'decimal:0,2', 'min:1', 'max:999999.99'],
            'budget_max' => ['exclude_unless:budget_mode,range', 'required', 'numeric', 'decimal:0,2', 'min:1', 'max:999999.99'],
            'work_mode' => ['required', 'string', Rule::in(array_keys(config('taskmate.work_modes', [])))],
            'workplace_name' => [$requiresWorkplace ? 'required' : 'nullable', 'string', 'max:255'],
            'workplace_address' => [$requiresWorkplace ? 'required' : 'nullable', 'string', 'max:255'],
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

    /**
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [function ($validator): void {
            if ($this->input('budget_mode') === 'range'
                && is_numeric($this->input('budget_min')) && is_numeric($this->input('budget_max'))
                && (float) $this->input('budget_max') < (float) $this->input('budget_min')) {
                $validator->errors()->add('budget_max', 'The maximum budget must be at least the minimum budget.');
            }
        }];
    }
}
