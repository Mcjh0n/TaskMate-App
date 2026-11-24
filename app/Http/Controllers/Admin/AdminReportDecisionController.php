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
            $decision = $request->string('decision')->toString();
            $decisions = [
                'start_review' => ['status' => ReportStatus::UnderReview, 'action' => 'report_review_started', 'event' => 'report.under_review'],
                'escalate' => ['status' => ReportStatus::UnderReview, 'action' => 'report_escalated', 'event' => 'report.under_review'],
                'resolve' => ['status' => ReportStatus::Resolved, 'action' => 'report_resolved', 'event' => 'report.resolved'],
                'dismiss' => ['status' => ReportStatus::Dismissed, 'action' => 'report_dismissed', 'event' => 'report.dismissed'],
            ];
            $decisionData = $decisions[$decision] ?? throw new \LogicException('Unsupported report review decision.');
            $report->status = $decisionData['status'];
            if ($decision === 'escalate') {
                $report->escalated_at = now()->toMutable();
                $report->escalated_by = $request->user()->id;
                $report->escalation_note = $request->validated('note');
            }
            $report->outcome = $decision === 'resolve' ? ReportOutcome::from((string) $request->validated('outcome')) : null;
            $report->admin_note = $request->validated('note');
            $report->resolution_message = $decision === 'resolve' ? $request->validated('resolution_message') : null;
            $report->reviewed_by = $request->user()->id;
            $report->reviewed_at = now()->toMutable();
            $report->save();

            $report->adminActions()->create([
                'admin_id' => $request->user()->id,
                'action' => $decisionData['action'],
                'note' => $request->validated('note'),
                'metadata' => ['decision' => $decision, 'outcome' => $report->outcome?->value],
            ]);
            $values = $decision === 'resolve' && $report->outcome !== null ? ['outcome' => $report->outcome->value] : [];
            $alerts->send(User::query()->findOrFail($report->reporter_id), $decisionData['event'], route('reports.show', $report->id, false), ['report_id' => $report->id], $values, $request->user()->id);
        });

        return back()->with('status', 'The report review was recorded. This does not order or enforce repayment.');
    }
}
