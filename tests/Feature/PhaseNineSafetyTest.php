<?php

namespace Tests\Feature;

use App\Enums\SanctionType;
use App\Enums\TaskStatus;
use App\Models\ContentFlag;
use App\Models\Task;
use App\Models\User;
use App\Models\UserSanction;
use App\Models\Report;
use App\Models\AdminAction;
use App\Services\OffPlatformScanner;
use App\Services\SanctionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Carbon;

class PhaseNineSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_off_platform_scanner_flags_bypass_requests_but_not_payment_arrangements(): void
    {
        $scanner = app(OffPlatformScanner::class);
        $this->assertSame([], $scanner->scan('paid via GCash after completion'));
        $matches = $scanner->scan("Let's skip the agreement and start before we sign");
        $this->assertContains('bypass_agreement', array_column($matches, 'category'));
    }

    public function test_content_flag_is_deduplicated_and_does_not_block_task_save(): void
    {
        $author = User::factory()->verified()->facultyStaff()->create();
        $task = Task::factory()->for($author, 'poster')->create(['title' => 'Message me on Telegram', 'status' => TaskStatus::PendingReview]);
        $scanner = app(OffPlatformScanner::class);
        $scanner->flag($task, $author, 'title', $task->title);
        $scanner->flag($task, $author, 'title', $task->title);

        $this->assertSame(1, ContentFlag::query()->where('flaggable_type', $task->getMorphClass())->where('flaggable_id', $task->id)->count());
        $this->assertSame(TaskStatus::PendingReview, $task->fresh()->status);
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

        $this->actingAs($admin)->post(route('admin.reports.decision', $report), [
            'decision' => 'escalate',
            'note' => 'Synthetic escalation marker.',
        ])->assertRedirect();
        $report->refresh();
        $this->assertNotNull($report->escalated_at);
        $this->assertSame($admin->id, $report->escalated_by);
        $this->assertDatabaseHas('admin_actions', ['action' => 'report_escalated', 'subject_type' => $report->getMorphClass(), 'subject_id' => $report->id]);
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
