<?php

namespace App\Http\Requests\Admin;

use App\Enums\ReportOutcome;
use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminReportDecisionRequest extends FormRequest
{
    /** @return array<string, string> */
    public static function decisions(Report $report): array
    {
        if (! $report->isOpenForReview()) {
            return [];
        }

        $decisions = [];
        if ($report->status === ReportStatus::Open) {
            $decisions['start_review'] = 'Start review';
        }

        $decisions['resolve'] = 'Resolve report';
        $decisions['dismiss'] = 'Dismiss report';
        if (config('taskmate.moderation.escalation_enabled', false)) {
            $decisions['escalate'] = 'Escalate';
        }

        return $decisions;
    }

    public function authorize(): bool
    {
        $report = $this->route('report');

        return $report instanceof Report && ($this->user()?->can('review', $report) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $decision = $this->input('decision');
        $resolving = $decision === 'resolve';
        $requiresNote = in_array($decision, ['resolve', 'dismiss', 'escalate'], true);
        $report = $this->route('report');
        $decisions = $report instanceof Report ? self::decisions($report) : [];

        return [
            'decision' => ['required', Rule::in(array_keys($decisions))],
            'outcome' => [$resolving ? 'required' : 'nullable', Rule::in(array_column(ReportOutcome::cases(), 'value'))],
            'note' => [$requiresNote ? 'required' : 'nullable', 'string', 'max:3000'],
            'resolution_message' => ['nullable', 'string', 'max:3000'],
        ];
    }

    public function messages(): array
    {
        return ['note.required' => 'Add a note for this report decision.'];
    }
}
