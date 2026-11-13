<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReportOutcome;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminReportDecisionRequest;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class AdminReportDecisionController extends Controller
{
    public function __invoke(AdminReportDecisionRequest $request, Report $report): RedirectResponse
    {
        DB::transaction(function () use ($request, $report): void {
            $report = Report::query()->lockForUpdate()->findOrFail($report->id);
            abort_unless($request->user()->can('review', $report), 403);
            $decision = $request->validated('decision');
            $report->status = match ($decision) {
                'start_review' => ReportStatus::UnderReview,
                'resolve' => ReportStatus::Resolved,
                'dismiss' => ReportStatus::Dismissed,
            };
            $report->outcome = $decision === 'resolve' ? ReportOutcome::from($request->validated('outcome')) : null;
            $report->admin_note = $request->validated('note');
            $report->reviewed_by = $request->user()->id;
            $report->reviewed_at = now();
            $report->save();

            $report->adminActions()->create([
                'admin_id' => $request->user()->id,
                'action' => match ($decision) {
                    'start_review' => 'report_review_started',
                    'resolve' => 'report_resolved',
                    'dismiss' => 'report_dismissed',
                },
                'note' => $request->validated('note'),
                'metadata' => ['decision' => $decision, 'outcome' => $report->outcome?->value],
            ]);
        });

        return back()->with('status', 'The report review was recorded. This does not order or enforce repayment.');
    }
}
