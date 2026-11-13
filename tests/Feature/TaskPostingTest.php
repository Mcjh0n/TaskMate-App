<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Enums\UserType;
use App\Enums\VerificationStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskPostingTest extends TestCase
{
    use RefreshDatabase;

    private function taskData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Help organize a campus workshop',
            'category' => 'event_support',
            'description' => 'Help prepare and organize a synthetic campus workshop.',
            'required_activities' => 'Arrange chairs and prepare sign-in sheets.',
            'deliverables' => 'A ready workshop room and completed sign-in sheets.',
            'estimated_duration_hours' => 4,
            'skill_requirements' => 'Organized and reliable.',
            'complexity' => 'low',
            'budget' => 1500,
            'work_mode' => 'onsite',
            'workplace_name' => 'Synthetic Student Center',
            'workplace_address' => 'Synthetic campus room 101',
            'physical_demand' => 'none',
            'academic_involvement' => 'none',
            'hazard_exposure' => '0',
            'private_meeting_required' => '0',
            'workplace_setting' => 'on_campus',
            'deadline' => now()->addWeek()->toDateString(),
            'guidelines_confirmed' => '1',
        ], $overrides);
    }

    private function verifiedPoster(UserType $type = UserType::FacultyStaff): User
    {
        return User::factory()->verified()->state(['user_type' => $type])->create();
    }

    public function test_verified_eligible_account_types_can_create_pending_review_tasks(): void
    {
        config(['taskmate.students_can_post' => true]);

        foreach ([UserType::FacultyStaff, UserType::Organization, UserType::Student] as $type) {
            $poster = $this->verifiedPoster($type);
            $this->actingAs($poster)->post(route('tasks.store'), $this->taskData())->assertRedirect();
            $task = $poster->tasks()->latest('id')->firstOrFail();
            $this->assertSame(TaskStatus::PendingReview, $task->status);
            $this->assertNotNull($task->guidelines_confirmed_at);
        }
    }

    public function test_unverified_admin_and_disallowed_student_cannot_create_tasks(): void
    {
        $unverified = User::factory()->create();
        $rejected = User::factory()->state(['verification_status' => VerificationStatus::Rejected])->create();
        $admin = $this->verifiedPoster(UserType::FacultyStaff);
        $admin->forceFill(['is_admin' => true])->save();
        config(['taskmate.students_can_post' => false]);
        $student = $this->verifiedPoster(UserType::Student);

        $this->actingAs($unverified)->post(route('tasks.store'), $this->taskData())->assertRedirect();
        $this->actingAs($rejected)->post(route('tasks.store'), $this->taskData())->assertRedirect();
        $this->actingAs($admin)->post(route('tasks.store'), $this->taskData())->assertForbidden();
        $this->actingAs($student)->post(route('tasks.store'), $this->taskData())->assertForbidden();
    }

    public function test_task_fields_and_guideline_confirmation_are_validated(): void
    {
        $poster = $this->verifiedPoster();
        $this->actingAs($poster)->post(route('tasks.store'), [])->assertSessionHasErrors([
            'title', 'category', 'description', 'required_activities', 'deliverables',
            'estimated_duration_hours', 'complexity', 'budget', 'work_mode', 'guidelines_confirmed',
            'physical_demand', 'academic_involvement', 'hazard_exposure', 'private_meeting_required',
        ]);
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_invalid_choices_limits_past_deadline_and_missing_workplace_are_rejected(): void
    {
        $poster = $this->verifiedPoster();
        $this->actingAs($poster)->post(route('tasks.store'), $this->taskData(['category' => 'invented']))->assertSessionHasErrors('category');
        $this->post(route('tasks.store'), $this->taskData(['work_mode' => 'teleport']))->assertSessionHasErrors('work_mode');
        $this->post(route('tasks.store'), $this->taskData(['workplace_name' => '', 'workplace_address' => '']))->assertSessionHasErrors(['workplace_name', 'workplace_address']);
        $this->post(route('tasks.store'), $this->taskData(['work_mode' => 'hybrid', 'workplace_name' => '', 'workplace_address' => '']))->assertSessionHasErrors(['workplace_name', 'workplace_address']);
        $this->post(route('tasks.store'), $this->taskData(['budget' => 0]))->assertSessionHasErrors('budget');
        $this->post(route('tasks.store'), $this->taskData(['budget' => 1000001]))->assertSessionHasErrors('budget');
        $this->post(route('tasks.store'), $this->taskData(['estimated_duration_hours' => 0.4]))->assertSessionHasErrors('estimated_duration_hours');
        $this->post(route('tasks.store'), $this->taskData(['estimated_duration_hours' => 501]))->assertSessionHasErrors('estimated_duration_hours');
        $this->post(route('tasks.store'), $this->taskData(['deadline' => now()->subDay()->toDateString()]))->assertSessionHasErrors('deadline');
        $this->post(route('tasks.store'), $this->taskData(['guidelines_confirmed' => '0']))->assertSessionHasErrors('guidelines_confirmed');
    }

    public function test_remote_task_discards_workplace_details_and_untrusted_status_fields(): void
    {
        $poster = $this->verifiedPoster();
        $this->actingAs($poster)->post(route('tasks.store'), $this->taskData([
            'work_mode' => 'remote',
            'workplace_name' => 'Private home',
            'workplace_address' => 'Should not be retained',
            'status' => 'approved',
            'reviewed_by' => $poster->id,
        ]))->assertRedirect();

        $task = Task::firstOrFail();
        $this->assertSame(TaskStatus::PendingReview, $task->status);
        $this->assertNull($task->workplace_name);
        $this->assertNull($task->workplace_address);
        $this->assertNull($task->reviewed_by);
    }

    public function test_poster_can_only_view_update_and_cancel_their_own_tasks(): void
    {
        $owner = $this->verifiedPoster();
        $other = $this->verifiedPoster();
        $task = Task::factory()->create(['poster_id' => $owner->id]);

        $this->actingAs($owner)->get(route('tasks.show', $task))->assertOk()->assertInertia(fn ($page) => $page->component('tasks/show')->has('task.title'));
        $this->actingAs($other)->get(route('tasks.show', $task))->assertForbidden();
        $this->actingAs($other)->get(route('tasks.edit', $task))->assertForbidden();
        $this->actingAs($owner)->put(route('tasks.update', $task), $this->taskData(['work_mode' => 'remote']))->assertRedirect();
        $this->actingAs($owner)->post(route('tasks.cancel', $task))->assertRedirect(route('tasks.mine'));
        $this->assertSame(TaskStatus::Cancelled, $task->fresh()->status);
        $this->actingAs($owner)->get(route('tasks.edit', $task))->assertForbidden();
    }

    public function test_editing_approved_task_resubmits_and_clears_review_note(): void
    {
        $poster = $this->verifiedPoster();
        $task = Task::factory()->create([
            'poster_id' => $poster->id,
            'status' => TaskStatus::Approved,
            'review_note' => 'Previously approved note',
            'reviewed_by' => User::factory()->create()->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($poster)->put(route('tasks.update', $task), $this->taskData(['work_mode' => 'remote']))->assertRedirect();
        $this->assertSame(TaskStatus::PendingReview, $task->fresh()->status);
        $this->assertNull($task->fresh()->review_note);
        $this->assertNull($task->fresh()->reviewed_by);
        $this->assertNull($task->fresh()->reviewed_at);
    }

    public function test_rejected_and_cancelled_tasks_cannot_be_edited_or_cancelled_again(): void
    {
        $poster = $this->verifiedPoster();
        foreach ([TaskStatus::Rejected, TaskStatus::Cancelled] as $status) {
            $task = Task::factory()->create(['poster_id' => $poster->id, 'status' => $status]);
            $this->actingAs($poster)->get(route('tasks.edit', $task))->assertForbidden();
            $this->actingAs($poster)->post(route('tasks.cancel', $task))->assertForbidden();
        }
    }

    public function test_my_tasks_contains_only_the_current_posters_tasks(): void
    {
        $poster = $this->verifiedPoster();
        $other = $this->verifiedPoster();
        Task::factory()->create(['poster_id' => $poster->id, 'title' => 'My synthetic task']);
        Task::factory()->create(['poster_id' => $other->id, 'title' => 'Other synthetic task']);

        $this->actingAs($poster)->get(route('tasks.mine'))->assertOk()->assertInertia(fn ($page) => $page
            ->component('tasks/mine')
            ->has('tasks.data', 1)
            ->where('tasks.data.0.title', 'My synthetic task'));
    }

    public function test_safety_answers_require_hazard_details_and_in_person_setting(): void
    {
        $poster = $this->verifiedPoster();
        $this->actingAs($poster)->post(route('tasks.store'), $this->taskData([
            'hazard_exposure' => '1',
            'hazard_description' => '',
        ]))->assertSessionHasErrors('hazard_description');
        $this->post(route('tasks.store'), $this->taskData(['workplace_setting' => '']))
            ->assertSessionHasErrors('workplace_setting');

        $this->post(route('tasks.store'), $this->taskData([
            'work_mode' => 'remote',
            'workplace_name' => 'Ignore me',
            'workplace_address' => 'Ignore me',
            'workplace_setting' => 'off_campus_private_or_residential',
        ]))->assertRedirect();
        $this->assertNull(Task::latest('id')->firstOrFail()->workplace_setting);
    }
}
