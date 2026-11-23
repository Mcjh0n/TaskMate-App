<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportOutcome;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminReportDecisionRequest;
use App\Models\Report;
use App\Models\User;
use App\Services\TaskMateNotifications;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AdminReportDecisionController extends Controller
{
    public function __invoke(AdminReportDecisionRequest $request, Report $report, TaskMateNotifications $alerts): RedirectResponse
    {
        DB::transaction(function () use ($request, $report, $alerts): void {
            $report = Report::query()->lockForUpdate()->findOrFail($report->id);
            abort_unless($request->user()->can('review', $report), 403);
            $decision = $request->validated('decision');
            $report->status = match ($decision) {
                'start_review' => ReportStatus::UnderReview,
                'escalate' => ReportStatus::UnderReview,
                'resolve' => ReportStatus::Resolved,
                'dismiss' => ReportStatus::Dismissed,
            };
            if ($decision === 'escalate') {
                $report->escalated_at = now();
                $report->escalated_by = $request->user()->id;
                $report->escalation_note = $request->validated('note');
            }
            $report->outcome = $decision === 'resolve' ? ReportOutcome::from($request->validated('outcome')) : null;
            $report->admin_note = $request->validated('note');
            $report->resolution_message = $decision === 'resolve' ? $request->validated('resolution_message') : null;
            $report->reviewed_by = $request->user()->id;
            $report->reviewed_at = now();
            $report->save();

            $report->adminActions()->create([
                'admin_id' => $request->user()->id,
                'action' => match ($decision) {
                    'start_review' => 'report_review_started',
                    'escalate' => 'report_escalated',
                    'resolve' => 'report_resolved',
                    'dismiss' => 'report_dismissed',
                },
                'note' => $request->validated('note'),
                'metadata' => ['decision' => $decision, 'outcome' => $report->outcome?->value],
            ]);
            $event = match ($decision) {
                'start_review', 'escalate' => 'report.under_review',
                'resolve' => 'report.resolved',
                'dismiss' => 'report.dismissed',
            };
            $values = $decision === 'resolve' ? ['outcome' => $report->outcome?->value ?? 'inconclusive'] : [];
            $alerts->send(User::query()->findOrFail($report->reporter_id), $event, route('reports.show', $report->id, false), ['report_id' => $report->id], $values, $request->user()->id);
        });

        return back()->with('status', 'The report review was recorded. This does not order or enforce repayment.');
    }
}
