<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\TaskStatus;
use App\Enums\VerificationStatus;
use App\Models\Application;
use App\Models\Task;
use App\Models\User;
use App\Models\TaskScreening;
use App\Models\Report;
use App\Models\UserSanction;
use App\Enums\SanctionType;
use App\Enums\ReportType;
use App\Enums\ReportStatus;
use App\Enums\ReportOutcome;
use App\Services\OffPlatformScanner;
use App\Models\Agreement;
use App\Models\AgreementVersion;
use App\Models\AgreementConfirmation;
use App\Models\PaymentEvent;
use App\Models\TaskCompletion;
use App\Models\Rating;
use App\Enums\AgreementStatus;
use App\Enums\PaymentEventType;
use App\Enums\RatingDirection;
use App\Services\AgreementContentHasher;
use Illuminate\Database\Seeder;

class DemoScenarioSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $this->call(LocalTaskMateSeeder::class);
        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $faculty = User::where('email', 'faculty@example.test')->firstOrFail();
        $student = User::where('email', 'student1@example.test')->firstOrFail();

        foreach ([
            ['Scenario: Pending review', TaskStatus::PendingReview],
            ['Scenario: Changes requested', TaskStatus::ChangesRequested],
            ['Scenario: Approved', TaskStatus::Approved],
            ['Scenario: Rejected', TaskStatus::Rejected],
            ['Scenario: System-blocked', TaskStatus::Rejected],
            ['Scenario: Assigned', TaskStatus::Assigned],
            ['Scenario: In progress', TaskStatus::InProgress],
            ['Scenario: Completed', TaskStatus::Completed],
            ['Scenario: Removed', TaskStatus::Removed],
            ['Scenario: Cancelled', TaskStatus::Cancelled],
            ['Scenario: High-risk escalated', TaskStatus::PendingReview],
            ['Scenario: Please message me on Telegram', TaskStatus::Approved],
        ] as [$title, $status]) {
            $task = Task::query()->where('title', $title)->first() ?? new Task();
            $task->poster()->associate($faculty);
            $task->title = $title;
            $task->category = array_key_first(config('taskmate.categories'));
            $task->description = 'Synthetic scenario record for local testing only.';
            $task->required_activities = 'Review the clearly synthetic scenario details.';
            $task->deliverables = 'A synthetic sample deliverable.';
            $task->estimated_duration_hours = 2;
            $task->skill_requirements = null;
            $task->complexity = 'low';
            $task->budget = 500;
            $task->work_mode = 'remote';
            $task->workplace_name = null;
            $task->workplace_address = null;
            $task->physical_demand = 'none';
            $task->academic_involvement = 'none';
            $task->hazard_exposure = false;
            $task->hazard_description = null;
            $task->private_meeting_required = false;
            $task->workplace_setting = null;
            $task->status = $status;
            $task->guidelines_confirmed_at = now();
            $task->risk_level = $title === 'Scenario: High-risk escalated' ? 'high' : 'low';
            $task->risk_score = $title === 'Scenario: High-risk escalated' ? 8 : 0;
            $task->decision_source = $title === 'Scenario: High-risk escalated' ? null : 'admin';
            $task->screened_at = now();
            $task->reviewed_by = in_array($status, [TaskStatus::Approved, TaskStatus::Rejected, TaskStatus::ChangesRequested], true) ? $admin->id : null;
            $task->reviewed_at = in_array($status, [TaskStatus::Approved, TaskStatus::Rejected, TaskStatus::ChangesRequested], true) ? now() : null;
            $task->poster()->associate($faculty);
            $task->status = $status;
            if ($status === TaskStatus::ChangesRequested) $task->review_note = 'Synthetic note requesting clearer details.';
            if ($status === TaskStatus::Rejected) $task->review_note = 'Synthetic rejected task scenario.';
            if ($title === 'Scenario: High-risk escalated') {
                $task->risk_level = 'high'; $task->risk_score = 8; $task->decision_source = null;
            }
            if ($title === 'Scenario: System-blocked') {
                $task->risk_level = 'high'; $task->risk_score = 9; $task->decision_source = 'system';
                $task->review_note = 'This synthetic task was blocked for the local demo scenario.'; $task->reviewed_by = null;
            }
            if (in_array($status, [TaskStatus::Approved, TaskStatus::Rejected, TaskStatus::ChangesRequested], true)) {
                $task->reviewed_by = $admin->id; $task->reviewed_at = now();
            }
            if ($title === 'Scenario: System-blocked') $task->reviewed_by = null;
            if ($status === TaskStatus::Removed) {
                $task->removed_by = $admin->id; $task->removed_at = now(); $task->removal_reason = 'Synthetic removal scenario.';
            }
            $task->save();

            if (in_array($title, ['Scenario: High-risk escalated', 'Scenario: System-blocked'], true) && ! $task->screenings()->exists()) {
                $screening = new TaskScreening(); $screening->task()->associate($task);
                $screening->risk_level = 'high'; $screening->risk_score = $title === 'Scenario: System-blocked' ? 9 : 8;
                $screening->factors = [['factor' => 'Synthetic scenario', 'points' => 8, 'reason' => 'Demo data only.']];
                $screening->keyword_hits = []; $screening->hard_triggers = []; $screening->outcome = $title === 'Scenario: System-blocked' ? 'blocked' : 'escalated'; $screening->save();
            }
            if ($title === 'Scenario: Please message me on Telegram') {
                app(OffPlatformScanner::class)->flag($task, $faculty, 'description', 'Please message me on Telegram');
            }

            if ($status === TaskStatus::Assigned) {
                $application = Application::where('task_id', $task->id)->where('applicant_id', $student->id)->first() ?? new Application();
                $application->task()->associate($task); $application->applicant()->associate($student);
                $application->message = 'Synthetic application for local demo only.';
                $application->status = ApplicationStatus::Accepted; $application->responded_at = now(); $application->save();
                if (! Agreement::where('task_id', $task->id)->exists()) {
                    $agreement = new Agreement(); $agreement->task()->associate($task); $agreement->application()->associate($application);
                    $agreement->poster()->associate($faculty); $agreement->student()->associate($student);
                    $agreement->status = AgreementStatus::Negotiating; $agreement->save();
                }
            }
        }

        $moreInfo = User::where('email', 'student.pending@example.test')->firstOrFail();
        $moreInfo->verification_status = VerificationStatus::MoreInfoRequested;
        $moreInfo->verification_note = 'Synthetic request for updated demo details.';
        $moreInfo->save();
        $rejected = User::firstOrCreate(['email' => 'student.rejected@example.test'], [
            'name' => 'Synthetic Rejected Student', 'user_type' => 'student', 'affiliation' => 'Demo College', 'id_number' => 'FAKE-STU-REJ',
            'password' => 'password', 'email_verified_at' => now(),
        ]);
        $rejected->verification_status = VerificationStatus::Rejected;
        $rejected->verification_note = 'Synthetic rejected account scenario.';
        $rejected->save();

        foreach ([
            ['warning', $student, SanctionType::Warning],
            ['suspension', $moreInfo, SanctionType::Suspension],
            ['ban', $rejected, SanctionType::Ban],
        ] as [$label, $user, $type]) {
            if (UserSanction::where('user_id', $user->id)->where('reason', 'Synthetic '.$label.' scenario.')->exists()) continue;
            $sanction = new UserSanction(); $sanction->user()->associate($user); $sanction->type = $type;
            $sanction->reason = 'Synthetic '.$label.' scenario.'; $sanction->internal_note = 'Synthetic local testing record.';
            $sanction->starts_at = now()->subDay(); $sanction->ends_at = $type === SanctionType::Suspension ? now()->addDays(7) : null;
            $sanction->issuer()->associate($admin); $sanction->save();
        }

        $reportTask = Task::where('title', 'Scenario: Completed')->firstOrFail();
        foreach ([
            ['open', ReportStatus::Open, null],
            ['under-review', ReportStatus::UnderReview, null],
            ['escalated', ReportStatus::UnderReview, null],
            ['resolved', ReportStatus::Resolved, ReportOutcome::Inconclusive],
        ] as [$label, $status, $outcome]) {
            if (Report::where('description', 'Synthetic '.$label.' report scenario.')->exists()) continue;
            $report = new Report(); $report->reporter()->associate($student); $report->reportedUser()->associate($faculty);
            $report->task()->associate($reportTask); $report->type = ReportType::Other; $report->description = 'Synthetic '.$label.' report scenario.';
            $report->status = $status; $report->outcome = $outcome; $report->priority = $label === 'escalated' ? 'high' : 'normal';
            if ($label === 'escalated') { $report->escalated_at = now(); $report->escalated_by = $admin->id; $report->escalation_note = 'Synthetic escalation marker.'; }
            if ($status === ReportStatus::Resolved) { $report->reviewed_by = $admin->id; $report->reviewed_at = now(); $report->admin_note = 'Synthetic resolution note.'; }
            $report->save();
        }

        foreach (['unpaid', 'marked-paid', 'received', 'not-received'] as $paymentCase) {
            $task = Task::query()->where('title', 'Scenario: Payment '.$paymentCase)->first() ?? new Task();
            $task->poster()->associate($faculty); $task->title = 'Scenario: Payment '.$paymentCase;
            $task->category = array_key_first(config('taskmate.categories')); $task->description = 'Synthetic payment-status scenario.';
            $task->required_activities = 'Synthetic activity.'; $task->deliverables = 'Synthetic deliverable.';
            $task->estimated_duration_hours = 1; $task->complexity = 'low'; $task->budget = 500; $task->work_mode = 'remote';
            $task->status = TaskStatus::InProgress; $task->guidelines_confirmed_at = now();
            $task->physical_demand = 'none'; $task->academic_involvement = 'none';
            $task->hazard_exposure = false; $task->private_meeting_required = false; $task->workplace_setting = null;
            $task->status = TaskStatus::InProgress; $task->save();
            $application = Application::where('task_id', $task->id)->where('applicant_id', $student->id)->first() ?? new Application();
            $application->task()->associate($task); $application->applicant()->associate($student);
            $application->message = 'Synthetic accepted application.';
            $application->status = ApplicationStatus::Accepted; $application->responded_at = now(); $application->save();
            $agreement = Agreement::where('task_id', $task->id)->first();
            if (! $agreement) $agreement = new Agreement();
            $agreement->task()->associate($task); $agreement->application()->associate($application);
            $agreement->poster()->associate($faculty); $agreement->student()->associate($student);
            if (! $agreement->exists) { $agreement->status = AgreementStatus::Agreed; $agreement->locked_at = now(); $agreement->save(); }
            if (! $agreement->current_version_id) {
                $version = new AgreementVersion(); $version->agreement()->associate($agreement); $version->version_number = 1;
                $version->proposed_by = $faculty->id; $version->deliverables = 'Synthetic agreed deliverable.';
                $version->terms = 'Synthetic agreement terms for local testing only.'; $version->final_amount = 500;
                $version->start_date = today()->subDay(); $version->due_date = today()->addDays(5);
                $version->work_mode = 'remote'; $version->payment_arrangement = 'Synthetic direct payment arrangement after completion.';
                $version->comment = 'Synthetic demo proposal.'; $version->created_at = now();
                $version->content_hash = app(AgreementContentHasher::class)->hash($version); $version->save();
                $agreement->current_version_id = $version->id; $agreement->status = AgreementStatus::Agreed; $agreement->locked_at = now(); $agreement->save();
                foreach ([$faculty, $student] as $party) { $confirmation = new AgreementConfirmation(); $confirmation->agreement_version_id = $version->id; $confirmation->user_id = $party->id; $confirmation->confirmed_at = now(); $confirmation->save(); }
            }
            if ($paymentCase === 'marked-paid' || $paymentCase === 'received' || $paymentCase === 'not-received') {
                $event = new PaymentEvent(); $event->agreement_id = $agreement->id; $event->actor_id = $faculty->id;
                $event->event = PaymentEventType::MarkedPaid; $event->amount = 500; $event->paid_on = today(); $event->method = 'Synthetic demo'; $event->note = 'Synthetic local data.'; $event->created_at = now(); $event->save();
            }
            if ($paymentCase === 'received' || $paymentCase === 'not-received') {
                $event = new PaymentEvent(); $event->agreement_id = $agreement->id; $event->actor_id = $student->id;
                $event->event = $paymentCase === 'received' ? PaymentEventType::ConfirmedReceived : PaymentEventType::ReportedNotReceived;
                $event->amount = $paymentCase === 'received' ? 500 : null; $event->note = 'Synthetic local payment response.'; $event->created_at = now(); $event->save();
            }
            if ($paymentCase === 'received') {
                $completion = new TaskCompletion(); $completion->agreement_id = $agreement->id; $completion->completed_at = now();
                $completion->completed_by = $faculty->id; $completion->completion_note = 'Synthetic completion record.'; $completion->save();
                $task->status = TaskStatus::Completed; $task->save();
                if (! Rating::where('agreement_id', $agreement->id)->exists()) {
                    $rating = new Rating(); $rating->agreement_id = $agreement->id; $rating->task_id = $task->id;
                    $rating->rater_id = $faculty->id; $rating->ratee_id = $student->id; $rating->direction = RatingDirection::PosterToStudent;
                    $rating->overall_experience = 5; $rating->comment = 'Synthetic local feedback.'; $rating->save();
                    foreach (array_keys(config('taskmate.ratings.dimensions.poster_to_student')) as $dimension) $rating->scores()->create(['dimension' => $dimension, 'score' => 5]);
                }
            } elseif ($paymentCase === 'unpaid') {
                $completion = new TaskCompletion(); $completion->agreement_id = $agreement->id; $completion->completed_at = now();
                $completion->completed_by = $faculty->id; $completion->completion_note = 'Synthetic completed task without a rating.'; $completion->save();
                $task->status = TaskStatus::Completed; $task->save();
            }
        }
    }
}
