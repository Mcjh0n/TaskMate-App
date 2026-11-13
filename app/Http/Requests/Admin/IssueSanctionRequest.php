<?php

namespace App\Http\Requests\Admin;

use App\Models\Report;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IssueSanctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');
        if (! $target instanceof User || ! $this->user()?->isAdmin() || $target->isAdmin() || $target->id === $this->user()->id) return false;
        if ($reportId = $this->input('report_id')) {
            $report = Report::find($reportId);
            if (! $report || ! $this->user()->can('review', $report)) return false;
        }
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['warning', 'remove_post', 'require_verification', 'suspend', 'ban'])],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'internal_note' => ['required', 'string', 'min:3', 'max:3000'],
            'ends_at' => ['required_if:action,suspend', 'nullable', 'date', 'after:now', 'before_or_equal:'.now()->addDays((int) config('taskmate.sanctions.max_suspension_days', 90))->toDateTimeString()],
            'report_id' => ['nullable', 'exists:reports,id'],
            'task_id' => ['required_if:action,remove_post', 'nullable', 'exists:tasks,id'],
        ];
    }
}
