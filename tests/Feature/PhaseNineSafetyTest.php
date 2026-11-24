<?php

namespace Tests\Feature;

use App\Enums\SanctionType;
use App\Enums\TaskStatus;
use App\Models\ContentFlag;
use App\Models\Report;
use App\Models\Task;
use App\Models\User;
use App\Models\UserSanction;
use App\Services\OffPlatformScanner;
use App\Services\SanctionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PhaseNineSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_off_platform_scanner_flags_bypass_requests_but_not_payment_arrangements(): void
    {
        config(['taskmate.offplatform.enabled' => true]);
        $scanner = app(OffPlatformScanner::class);
        $this->assertSame([], $scanner->scan('paid via GCash after completion'));
        $matches = $scanner->scan("Let's skip the agreement and start before we sign");
        $this->assertContains('bypass_agreement', array_column($matches, 'category'));
    }

    public function test_content_flag_is_deduplicated_and_does_not_block_task_save(): void
    {
        config(['taskmate.offplatform.enabled' => true]);
        $author = User::factory()->verified()->facultyStaff()->create();
        $admin = User::factory()->verified()->facultyStaff()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $otherAdmin = User::factory()->verified()->facultyStaff()->create();
        $otherAdmin->forceFill(['is_admin' => true])->save();
        $task = Task::factory()->for($author, 'poster')->create(['title' => 'Message me on Telegram', 'status' => TaskStatus::PendingReview]);
        $scanner = app(OffPlatformScanner::class);
        $scanner->flag($task, $author, 'title', $task->title);
        $scanner->flag($task, $author, 'title', $task->title);

        $this->assertSame(1, ContentFlag::query()->where('flaggable_type', $task->getMorphClass())->where('flaggable_id', $task->id)->count());
        $this->assertSame(TaskStatus::PendingReview, $task->fresh()->status);
        $this->assertSame('flag.new', $admin->notifications()->firstOrFail()->data['event']);
        $this->assertSame(1, $otherAdmin->notifications()->count());
        $this->assertSame(0, $author->notifications()->count());
    }

    public function test_suspension_is_computed_from_current_time_and_lift_state(): void
    {
        $user = User::factory()->verified()->create();
        $admin = User::factory()->create();
        $sanction = new UserSanction;
        $sanction->user_id = $user->id;
        $sanction->type = SanctionType::Suspension;
        $sanction->reason = 'Synthetic test reason';
        $sanction->starts_at = now()->subDay();
        $sanction->ends_at = now()->addDay();
        $sanction->issued_by = $admin->id;
        $sanction->save();

        $service = app(SanctionService::class);
        $this->assertSame($sanction->id, $service->suspended($user)->id);
        Carbon::setTestNow(now()->addDays(2));
        $this->assertNull($service->suspended($user));
        Carbon::setTestNow();
    }

    public function test_verified_user_can_report_a_related_task_and_admin_can_escalate_with_audit(): void
    {
        $poster = User::factory()->verified()->facultyStaff()->create();
        $reporter = User::factory()->verified()->create();
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();
        $task = Task::factory()->for($poster, 'poster')->approved()->create();

        $this->actingAs($reporter)->post(route('tasks.problem-reports.store', $task), [
            'type' => 'harassment',
            'description' => 'A synthetic report for automated testing.',
        ])->assertRedirect();
        $report = Report::query()->firstOrFail();
        $this->assertSame('high', $report->priority);
        $this->assertSame($poster->id, $report->reported_user_id);
        $this->assertSame(0, $poster->notifications()->count(), 'A non-replyable report must not alert the reported user.');
        $this->assertSame('report.priority', $admin->notifications()->firstOrFail()->data['event']);

        $this->actingAs($admin)->get(route('admin.reports.show', $report))->assertInertia(fn (Assert $page) => $page
            ->where('decisionOptions', [
                ['value' => 'start_review', 'label' => 'Start review'],
                ['value' => 'resolve', 'label' => 'Resolve report'],
                ['value' => 'dismiss', 'label' => 'Dismiss report'],
            ])->where('canRemoveRelatedPost', true));
        $this->post(route('admin.reports.decision', $report), ['decision' => 'escalate', 'note' => 'Synthetic escalation marker.'])->assertInvalid('decision');
        $this->assertNull($report->fresh()->escalated_at);

        config(['taskmate.moderation.escalation_enabled' => true]);
        $this->actingAs($admin)->post(route('admin.reports.decision', $report), [
            'decision' => 'escalate',
            'note' => 'Synthetic escalation marker.',
        ])->assertRedirect();
        $report->refresh();
        $this->assertNotNull($report->escalated_at);
        $this->assertSame($admin->id, $report->escalated_by);
        $this->assertDatabaseHas('admin_actions', ['action' => 'report_escalated', 'subject_type' => $report->getMorphClass(), 'subject_id' => $report->id]);

        $otherTask = Task::factory()->for($poster, 'poster')->approved()->create();
        $action = [
            'report_id' => $report->id, 'action' => 'remove_post',
            'reason' => 'Synthetic reason for removal.', 'internal_note' => 'Synthetic admin note.',
        ];
        $this->post(route('admin.users.sanctions', $poster), $action + ['task_id' => $otherTask->id])->assertForbidden();
        $this->post(route('admin.users.sanctions', $poster), $action + ['task_id' => $task->id])->assertRedirect();
        $this->assertSame(TaskStatus::Approved, $otherTask->fresh()->status);
        $this->assertSame(TaskStatus::Removed, $task->fresh()->status);
    }

    public function test_student_awaiting_verification_can_report_an_approved_listing_and_track_the_report(): void
    {
        $poster = User::factory()->verified()->facultyStaff()->create();
        $student = User::factory()->create();
        $task = Task::factory()->for($poster, 'poster')->approved()->create();
        $pending = Task::factory()->for($poster, 'poster')->create(['status' => TaskStatus::PendingReview]);
        $data = ['type' => 'unsafe_behavior', 'description' => 'Synthetic concern about the stated work location.'];
        $url = route('tasks.problem-reports.store', $task);

        $this->actingAs($student)->get(route('browse.show', $task))->assertInertia(fn (Assert $page) => $page
            ->where('problemReport.action', $url)->where('canApply', false));
        $this->post(route('tasks.problem-reports.store', $pending), $data)->assertForbidden();
        $this->post($url, $data)->assertRedirect();
        $report = Report::query()->sole();
        $this->assertSame($student->id, $report->reporter_id);
        $this->assertSame($task->id, $report->task_id);
        $this->get(route('reports.show', $report))->assertOk();
        $this->get(route('reports.mine'))->assertInertia(fn (Assert $page) => $page->where('reports.data.0.id', $report->id));

        $this->actingAs(User::factory()->create())->get(route('reports.show', $report))->assertForbidden();
        $this->actingAs(User::factory()->facultyStaff()->create())->post($url, $data)->assertForbidden();
    }

    public function test_suspended_verified_student_cannot_open_browse(): void
    {
        $user = User::factory()->verified()->create();
        $admin = User::factory()->create();
        $sanction = new UserSanction;
        $sanction->user_id = $user->id;
        $sanction->type = SanctionType::Suspension;
        $sanction->reason = 'Synthetic test restriction';
        $sanction->starts_at = now()->subMinute();
        $sanction->ends_at = now()->addDay();
        $sanction->issued_by = $admin->id;
        $sanction->save();

        $this->actingAs($user)->get('/browse')->assertForbidden();
        $this->actingAs($user)->get(route('dashboard', $user->currentTeam->slug))->assertOk();
        $this->actingAs($user)->get('/my-sanctions')->assertOk();
    }
}
