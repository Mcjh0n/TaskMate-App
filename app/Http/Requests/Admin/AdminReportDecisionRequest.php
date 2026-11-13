<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReportOutcome;
use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminReportDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('report');
        return $report instanceof Report && ($this->user()?->can('review', $report) ?? false);
    }

    public function rules(): array
    {
        $decision = $this->input('decision');
        $resolving = $decision === 'resolve';
        $requiresNote = in_array($decision, ['resolve', 'dismiss', 'escalate'], true);
        return [
            'decision' => ['required', Rule::in(['start_review', 'escalate', 'resolve', 'dismiss'])],
            'outcome' => [$resolving ? 'required' : 'nullable', Rule::in(array_column(ReportOutcome::cases(), 'value'))],
            'note' => [$requiresNote ? 'required' : 'nullable', 'string', 'max:3000'],
            'resolution_message' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function messages(): array { return ['note.required' => 'Add a note for this report decision.']; }
}
