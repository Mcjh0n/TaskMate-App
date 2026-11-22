<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\UserType;
use App\Models\Task;
use App\Models\TaskScreening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TaskReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function verifiedPoster(UserType $type = UserType::FacultyStaff): User
    {
        return User::factory()->verified()->state(['user_type' => $type])->create();
    }

    private function formData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'A synthetic campus task',
            'category' => 'event_support',
            'description' => 'Prepare materials for a synthetic campus event.',
            'budget' => 1000,
            'work_mode' => 'remote',
            'guidelines_confirmed' => '1',
        ], $overrides);
    }

    public function test_every_new_task_stays_pending_even_when_old_automation_settings_are_enabled(): void
    {
        config(['taskmate.auto_approval_enabled' => true, 'taskmate.high_risk_action' => 'block']);
        $poster = $this->verifiedPoster();
        $admin = $this->verifiedPoster();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($poster)->post(route('tasks.store'), $this->formData([
            'title' => 'Please take my exam and do my homework',
            'academic_involvement' => 'produces_graded_work',
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $task = $poster->tasks()->latest('id')->firstOrFail();
        $this->assertSame(TaskStatus::PendingReview, $task->status);
        $this->assertNull($task->risk_level);
        $this->assertNull($task->risk_score);
        $this->assertNull($task->screened_at);
        $this->assertNull($task->decision_source);
        $this->assertSame(0, $task->screenings()->count());
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $admin->id, 'data->event' => 'task.awaiting_review']);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $admin->id, 'data->event' => 'task.priority_review']);
    }

    public function test_editing_a_previously_approved_task_clears_legacy_assessment_and_returns_it_to_review(): void
    {
        $poster = $this->verifiedPoster();
        $admin = $this->verifiedPoster();
        $admin->forceFill(['is_admin' => true])->save();
        $task = Task::factory()->approved()->create([
            'poster_id' => $poster->id,
            'risk_level' => 'high',
            'risk_score' => 12,
            'screened_at' => now(),
            'decision_source' => 'system',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
        $legacyScreening = TaskScreening::factory()->create(['task_id' => $task->id, 'risk_level' => 'high', 'risk_score' => 12]);

        $this->actingAs($poster)->put(route('tasks.update', $task), $this->formData(['title' => 'Edited synthetic task']))
            ->assertRedirect()->assertSessionHasNoErrors();

        $task->refresh();
        $this->assertSame(TaskStatus::PendingReview, $task->status);
        $this->assertNull($task->risk_level);
        $this->assertNull($task->risk_score);
        $this->assertNull($task->screened_at);
        $this->assertNull($task->decision_source);
        $this->assertDatabaseHas('task_screenings', ['id' => $legacyScreening->id, 'task_id' => $task->id, 'risk_score' => 12]);
        $this->assertSame(1, $task->screenings()->count(), 'Old screening history is retained but no new screening is created.');
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $admin->id, 'data->event' => 'task.awaiting_review']);
    }

    public function test_admin_queue_is_protected_filterable_and_shows_newest_pending_first(): void
    {
        $admin = $this->verifiedPoster();
        $admin->forceFill(['is_admin' => true])->save();
        $poster = $this->verifiedPoster();
        $older = Task::factory()->create(['poster_id' => $poster->id, 'title' => 'Needle older', 'work_mode' => 'remote', 'created_at' => now()->subHour()]);
        $newer = Task::factory()->create(['poster_id' => $poster->id, 'title' => 'Needle newer', 'work_mode' => 'remote', 'created_at' => now()]);
        Task::factory()->approved()->create(['poster_id' => $poster->id, 'title' => 'Needle approved', 'created_at' => now()->addMinute()]);
        Task::factory()->create(['poster_id' => $poster->id, 'title' => 'Different']);

        $this->get(route('admin.tasks.index'))->assertRedirect(route('login'));
        $this->actingAs($poster)->get(route('admin.tasks.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.tasks.index', ['search' => 'Needle']))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('admin/tasks/index')
            ->has('tasks.data', 3)
            ->where('tasks.data.0.id', $newer->id)
            ->where('tasks.data.1.id', $older->id)
            ->where('tasks.data.2.status', TaskStatus::Approved->value)
            ->missing('riskLevels')
            ->missing('tasks.data.0.riskLevel'));

        $this->get(route('admin.tasks.index', ['work_mode' => 'onsite']))
            ->assertInertia(fn (Assert $page) => $page->has('tasks.data', 2));
    }

    public function test_admin_task_decisions_are_manual_and_only_rejections_or_change_requests_need_notes(): void
    {
        $admin = $this->verifiedPoster();
        $admin->forceFill(['is_admin' => true])->save();
        $poster = $this->verifiedPoster();

        $approved = Task::factory()->create(['poster_id' => $poster->id, 'risk_level' => 'high', 'risk_score' => 9]);
        $this->actingAs($admin)->post(route('admin.tasks.decision', $approved), ['decision' => 'approve'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(TaskStatus::Approved, $approved->fresh()->status);
        $this->assertSame('admin', $approved->fresh()->decision_source);
        $this->assertDatabaseHas('admin_actions', ['subject_type' => Task::class, 'subject_id' => $approved->id, 'action' => 'task_approved']);
        $this->assertSame('task.approved', $poster->notifications()->firstOrFail()->data['event']);

        $rejected = Task::factory()->create(['poster_id' => $poster->id]);
        $this->post(route('admin.tasks.decision', $rejected), ['decision' => 'reject'])->assertSessionHasErrors('note');
        $this->post(route('admin.tasks.decision', $rejected), ['decision' => 'reject', 'note' => 'Please revise this synthetic task.'])->assertRedirect();
        $this->assertSame(TaskStatus::Rejected, $rejected->fresh()->status);

        $changes = Task::factory()->create(['poster_id' => $poster->id]);
        $this->post(route('admin.tasks.decision', $changes), ['decision' => 'request_changes'])->assertSessionHasErrors('note');
        $this->post(route('admin.tasks.decision', $changes), ['decision' => 'request_changes', 'note' => 'Please clarify this synthetic task.'])->assertRedirect();
        $this->assertSame(TaskStatus::ChangesRequested, $changes->fresh()->status);
    }

    public function test_admin_cannot_review_own_task_or_a_non_pending_task(): void
    {
        $admin = $this->verifiedPoster();
        $admin->forceFill(['is_admin' => true])->save();
        $ownTask = Task::factory()->create(['poster_id' => $admin->id]);
        $approved = Task::factory()->approved()->create(['poster_id' => $this->verifiedPoster()->id]);
        $nonAdmin = $this->verifiedPoster();

        $this->get(route('admin.tasks.show', $ownTask))->assertRedirect(route('login'));
        $this->actingAs($nonAdmin)->get(route('admin.tasks.show', $ownTask))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.tasks.show', $ownTask))->assertInertia(fn (Assert $page) => $page->where('canReview', false));
        $this->post(route('admin.tasks.decision', $ownTask), ['decision' => 'reject', 'note' => 'No'])->assertForbidden();
        $this->post(route('admin.tasks.decision', $approved), ['decision' => 'reject', 'note' => 'No'])->assertForbidden();
    }

    public function test_admin_task_review_props_no_longer_include_risk_levels_or_screening_details(): void
    {
        $admin = $this->verifiedPoster();
        $admin->forceFill(['is_admin' => true])->save();
        $poster = $this->verifiedPoster();
        $task = Task::factory()->create(['poster_id' => $poster->id, 'work_mode' => 'remote']);
        TaskScreening::factory()->create(['task_id' => $task->id]);

        $this->actingAs($poster)->get(route('tasks.show', $task))->assertInertia(fn (Assert $page) => $page
            ->missing('task.riskLevel')->missing('task.riskScore')->missing('task.decisionSource')->missing('task.screenedAt'));
        $this->actingAs($admin)->get(route('admin.tasks.show', $task))->assertInertia(fn (Assert $page) => $page
            ->component('admin/tasks/show')
            ->missing('screening')
            ->missing('screeningHistory')
            ->missing('task.riskLevel'));
    }

    public function test_legacy_screening_history_remains_immutable(): void
    {
        $screening = TaskScreening::factory()->create();

        try {
            $screening->risk_score = 100;
            $screening->save();
            $this->fail('A legacy screening row should remain immutable.');
        } catch (\LogicException $exception) {
            $this->assertSame('Task screening history is immutable.', $exception->getMessage());
        }
    }

    public function test_admin_task_review_returns_empty_related_data_as_safe_defaults(): void
    {
        $admin = $this->verifiedPoster();
        $admin->forceFill(['is_admin' => true])->save();
        $task = Task::factory()->create(['status' => TaskStatus::PendingReview]);

        $this->actingAs($admin)->get(route('admin.tasks.show', $task))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('admin/tasks/show')
            ->where('agreement', null)
            ->where('completionPayment', null)
            ->has('applications', 0)
            ->missing('screeningHistory')
            ->has('flags', 0));
    }
}
