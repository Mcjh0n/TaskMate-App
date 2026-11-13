<?php

namespace Tests\Feature;

use App\Enums\RiskLevel;
use App\Enums\TaskStatus;
use App\Enums\UserType;
use App\Models\Task;
use App\Models\TaskScreening;
use App\Models\User;
use App\Services\RiskScreening\RiskScreeningService;
use App\Services\RiskScreening\TaskModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RiskScreeningTest extends TestCase
{
    use RefreshDatabase;

    private function makeTask(array $attributes = []): Task
    {
        return Task::factory()->make(array_merge([
            'title' => 'A synthetic task',
            'category' => 'event_support',
            'description' => 'General help for an event.',
            'required_activities' => 'Set up chairs.',
            'deliverables' => 'A prepared event space.',
            'skill_requirements' => null,
            'work_mode' => 'remote',
            'workplace_name' => null,
            'workplace_address' => null,
            'workplace_setting' => null,
            'physical_demand' => 'none',
            'academic_involvement' => 'none',
            'hazard_exposure' => false,
            'private_meeting_required' => false,
            'guidelines_confirmed_at' => now(),
        ], $attributes));
    }

    private function verifiedPoster(): User
    {
        return User::factory()->verified()->state(['user_type' => UserType::FacultyStaff])->create();
    }

    private function formData(): array
    {
        return [
            'title' => 'A clear synthetic task',
            'category' => 'event_support',
            'description' => 'Prepare materials for a campus event.',
            'required_activities' => 'Sort event materials.',
            'deliverables' => 'Sorted and labeled materials.',
            'estimated_duration_hours' => 2,
            'skill_requirements' => '',
            'complexity' => 'low',
            'budget' => 1000,
            'work_mode' => 'remote',
            'workplace_name' => '',
            'workplace_address' => '',
            'physical_demand' => 'none',
            'academic_involvement' => 'none',
            'hazard_exposure' => '0',
            'private_meeting_required' => '0',
            'guidelines_confirmed' => '1',
        ];
    }

    public function test_each_configured_factor_adds_its_points_and_keywords_are_capped(): void
    {
        config([
            'taskmate.risk_rules.points.category_base' => ['event_support' => 1, 'default' => 0],
            'taskmate.risk_rules.points.physical_demand' => ['heavy' => 2],
            'taskmate.risk_rules.points.academic_involvement' => ['supporting' => 3],
            'taskmate.risk_rules.points.hazard_exposure' => ['yes' => 4, 'no' => 0],
            'taskmate.risk_rules.points.private_meeting_required' => ['yes' => 5, 'no' => 0],
            'taskmate.risk_rules.points.workplace_setting' => ['off_campus_public' => 6],
            'taskmate.risk_rules.points.keyword_per_hit' => 7,
            'taskmate.risk_rules.keyword_max_points' => 5,
            'taskmate.risk_rules.thresholds' => ['moderate_from' => 100, 'high_from' => 200],
            'taskmate.prohibited_keywords' => [],
            'taskmate.risk_keywords' => ['heavy lifting'],
        ]);
        $task = $this->makeTask([
            'title' => 'Heavy lifting for a demo',
            'category' => 'event_support',
            'work_mode' => 'onsite',
            'workplace_setting' => 'off_campus_public',
            'physical_demand' => 'heavy',
            'academic_involvement' => 'supporting',
            'hazard_exposure' => true,
            'private_meeting_required' => true,
        ]);

        $assessment = app(RiskScreeningService::class)->screen($task);

        $this->assertSame(26, $assessment->score);
        $this->assertSame([1, 2, 3, 4, 5, 6, 5], array_column($assessment->factors, 'points'));
        $this->assertSame('Heavy lifting', $assessment->keywordHits[0]['phrase']);
        $this->assertSame(RiskLevel::Low, $assessment->level);
    }

    public function test_keyword_only_risk_never_exceeds_moderate_and_prohibited_terms_force_moderate(): void
    {
        config([
            'taskmate.risk_rules.points.category_base' => ['default' => 0],
            'taskmate.risk_rules.points.physical_demand' => ['heavy' => 0],
            'taskmate.risk_rules.points.academic_involvement' => ['supporting' => 0],
            'taskmate.risk_rules.points.hazard_exposure' => ['yes' => 0, 'no' => 0],
            'taskmate.risk_rules.points.private_meeting_required' => ['yes' => 0, 'no' => 0],
            'taskmate.risk_rules.points.workplace_setting' => ['on_campus' => 0],
            'taskmate.risk_rules.points.keyword_per_hit' => 10,
            'taskmate.risk_rules.keyword_max_points' => 30,
            'taskmate.risk_rules.thresholds' => ['moderate_from' => 3, 'high_from' => 7],
            'taskmate.prohibited_keywords' => ['take my exam'],
            'taskmate.risk_keywords' => ['meet alone'],
        ]);
        $assessment = app(RiskScreeningService::class)->screen($this->makeTask([
            'title' => 'Please TAKE MY EXAM and meet alone',
        ]));

        $this->assertSame(20, $assessment->score);
        $this->assertSame(RiskLevel::Moderate, $assessment->level);
        $this->assertCount(2, $assessment->keywordHits);
    }

    public function test_hard_triggers_force_high_for_prohibited_category_and_graded_work(): void
    {
        config([
            'taskmate.prohibited_categories' => ['event_support'],
            'taskmate.risk_rules.thresholds' => ['moderate_from' => 1, 'high_from' => 10],
            'taskmate.risk_rules.points.category_base' => ['default' => 0],
            'taskmate.risk_rules.points.physical_demand' => ['none' => 0],
            'taskmate.risk_rules.points.academic_involvement' => ['produces_graded_work' => 0],
            'taskmate.risk_rules.points.hazard_exposure' => ['no' => 0],
            'taskmate.risk_rules.points.private_meeting_required' => ['no' => 0],
            'taskmate.risk_rules.points.workplace_setting' => [],
            'taskmate.prohibited_keywords' => [],
            'taskmate.risk_keywords' => [],
        ]);
        $categoryTrigger = app(RiskScreeningService::class)->screen($this->makeTask());
        $academicTrigger = app(RiskScreeningService::class)->screen($this->makeTask([
            'category' => 'other',
            'academic_involvement' => 'produces_graded_work',
        ]));

        $this->assertSame(RiskLevel::High, $categoryTrigger->level);
        $this->assertSame(RiskLevel::High, $academicTrigger->level);
        $this->assertCount(1, $categoryTrigger->hardTriggers);
        $this->assertCount(1, $academicTrigger->hardTriggers);
    }

    public function test_threshold_boundaries_use_configured_scores(): void
    {
        config([
            'taskmate.risk_rules.points.category_base' => ['a' => 2, 'b' => 3, 'c' => 7, 'default' => 0],
            'taskmate.risk_rules.thresholds' => ['moderate_from' => 3, 'high_from' => 7],
            'taskmate.prohibited_categories' => [],
            'taskmate.prohibited_keywords' => [],
            'taskmate.risk_keywords' => [],
        ]);
        $service = app(RiskScreeningService::class);

        $this->assertSame(RiskLevel::Low, $service->screen($this->makeTask(['category' => 'a']))->level);
        $this->assertSame(RiskLevel::Moderate, $service->screen($this->makeTask(['category' => 'b']))->level);
        $this->assertSame(RiskLevel::High, $service->screen($this->makeTask(['category' => 'c']))->level);
    }

    public function test_low_risk_auto_approval_requires_enabled_setting_verified_poster_and_remote_or_review_free_workplace(): void
    {
        $poster = $this->verifiedPoster();
        $moderation = app(TaskModerationService::class);
        $remote = Task::factory()->create(['poster_id' => $poster->id, 'work_mode' => 'remote', 'workplace_setting' => null]);
        config(['taskmate.auto_approval_enabled' => false]);
        $first = $moderation->screenAndApply($remote);
        $this->assertSame('needs_review', $first->outcome);
        $this->assertSame(TaskStatus::PendingReview, $remote->fresh()->status);

        config(['taskmate.auto_approval_enabled' => true]);
        $second = $moderation->screenAndApply($remote->fresh());
        $this->assertSame('auto_approved', $second->outcome);
        $this->assertSame(TaskStatus::Approved, $remote->fresh()->status);
        $this->assertSame('system', $remote->fresh()->decision_source);

        foreach (['onsite', 'hybrid'] as $mode) {
            $inPerson = Task::factory()->create(['poster_id' => $poster->id, 'work_mode' => $mode]);
            $assessment = $moderation->screenAndApply($inPerson);
            $this->assertSame('needs_review', $assessment->outcome);
            $this->assertSame(TaskStatus::PendingReview, $inPerson->fresh()->status);
        }
        $this->assertDatabaseCount('task_screenings', 4);

        config(['taskmate.in_person_requires_admin_review' => false]);
        $workplaceValidated = Task::factory()->create(['poster_id' => $poster->id, 'work_mode' => 'onsite']);
        $this->assertSame('auto_approved', $moderation->screenAndApply($workplaceValidated)->outcome);

        $unverified = User::factory()->state(['user_type' => UserType::FacultyStaff])->create();
        $unverifiedTask = Task::factory()->create(['poster_id' => $unverified->id, 'work_mode' => 'remote']);
        $this->assertSame('needs_review', $moderation->screenAndApply($unverifiedTask)->outcome);

        $missingGuidelineConfirmation = $this->makeTask(['work_mode' => 'remote', 'guidelines_confirmed_at' => null]);
        $missingGuidelineConfirmation->setRelation('poster', $poster);
        $this->assertSame('needs_review', app(RiskScreeningService::class)->screen($missingGuidelineConfirmation)->outcome);
    }

    public function test_moderate_needs_review_high_escalates_or_blocks_without_exposing_scores(): void
    {
        config([
            'taskmate.auto_approval_enabled' => true,
            'taskmate.risk_rules.points.hazard_exposure' => ['yes' => 3, 'no' => 0],
            'taskmate.risk_rules.thresholds' => ['moderate_from' => 3, 'high_from' => 7],
            'taskmate.prohibited_keywords' => [],
            'taskmate.risk_keywords' => [],
            'taskmate.high_risk_action' => 'escalate',
        ]);
        $poster = $this->verifiedPoster();
        $moderation = app(TaskModerationService::class);
        $moderate = Task::factory()->create(['poster_id' => $poster->id, 'work_mode' => 'remote', 'hazard_exposure' => true]);
        $assessment = $moderation->screenAndApply($moderate);
        $this->assertSame('needs_review', $assessment->outcome);
        $this->assertSame(TaskStatus::PendingReview, $moderate->fresh()->status);

        $high = Task::factory()->create(['poster_id' => $poster->id, 'academic_involvement' => 'produces_graded_work']);
        config(['taskmate.high_risk_action' => 'escalate']);
        $escalated = $moderation->screenAndApply($high);
        $this->assertSame('escalated', $escalated->outcome);
        $this->assertSame(TaskStatus::PendingReview, $high->fresh()->status);

        config(['taskmate.high_risk_action' => 'block']);
        $blockedTask = Task::factory()->create(['poster_id' => $poster->id, 'academic_involvement' => 'produces_graded_work']);
        $blocked = $moderation->screenAndApply($blockedTask);
        $this->assertSame('blocked', $blocked->outcome);
        $this->assertSame(TaskStatus::Rejected, $blockedTask->fresh()->status);
        $this->assertSame('system', $blockedTask->fresh()->decision_source);
        $this->assertStringContainsString('current platform rules', $blockedTask->fresh()->review_note);
        $this->assertStringNotContainsString((string) $blocked->score, $blockedTask->fresh()->review_note);
    }

    public function test_re_screening_after_approved_and_changes_requested_edits_appends_history(): void
    {
        config(['taskmate.auto_approval_enabled' => false, 'taskmate.prohibited_keywords' => [], 'taskmate.risk_keywords' => []]);
        $poster = $this->verifiedPoster();
        $moderation = app(TaskModerationService::class);

        foreach ([TaskStatus::Approved, TaskStatus::ChangesRequested] as $status) {
            $task = Task::factory()->create(['poster_id' => $poster->id, 'work_mode' => 'remote']);
            $moderation->screenAndApply($task);
            $task->status = $status;
            $task->review_note = 'Prior review note';
            $task->save();
            $before = $task->screenings()->count();

            $this->actingAs($poster)->put(route('tasks.update', $task), $this->formData())->assertRedirect();
            $this->assertSame($before + 1, $task->screenings()->count());
            $this->assertSame(TaskStatus::PendingReview, $task->fresh()->status);
            $this->assertNull($task->fresh()->review_note);
        }
    }

    public function test_admin_queue_is_protected_filterable_and_high_risk_first(): void
    {
        $admin = $this->verifiedPoster();
        $admin->forceFill(['is_admin' => true])->save();
        $poster = $this->verifiedPoster();
        $high = Task::factory()->create(['poster_id' => $poster->id, 'title' => 'Needle high', 'risk_level' => 'high', 'work_mode' => 'remote']);
        $low = Task::factory()->create(['poster_id' => $poster->id, 'title' => 'Needle low', 'risk_level' => 'low', 'work_mode' => 'onsite']);
        Task::factory()->create(['poster_id' => $poster->id, 'title' => 'Different', 'risk_level' => 'moderate']);

        $this->get(route('admin.tasks.index'))->assertRedirect(route('login'));
        $this->actingAs($poster)->get(route('admin.tasks.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.tasks.index', ['search' => 'Needle']))->assertOk()->assertInertia(fn ($page) => $page
            ->component('admin/tasks/index')
            ->has('tasks.data', 2)
            ->where('tasks.data.0.id', $high->id)
            ->where('tasks.data.1.id', $low->id));
        $this->get(route('admin.tasks.index', ['risk_level' => 'low', 'work_mode' => 'onsite']))->assertInertia(fn ($page) => $page->has('tasks.data', 1)->where('tasks.data.0.id', $low->id));
    }

    public function test_admin_task_decisions_require_notes_where_needed_and_are_audited(): void
    {
        $admin = $this->verifiedPoster();
        $admin->forceFill(['is_admin' => true])->save();
        $poster = $this->verifiedPoster();

        $approved = Task::factory()->create(['poster_id' => $poster->id, 'risk_level' => 'low']);
        $this->actingAs($admin)->post(route('admin.tasks.decision', $approved), ['decision' => 'approve'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(TaskStatus::Approved, $approved->fresh()->status);
        $this->assertSame('admin', $approved->fresh()->decision_source);
        $this->assertNotNull($approved->fresh()->reviewed_at);
        $this->assertSame($admin->id, $approved->fresh()->reviewed_by);
        $this->assertDatabaseHas('admin_actions', ['subject_type' => Task::class, 'subject_id' => $approved->id, 'action' => 'task_approved']);

        $rejected = Task::factory()->create(['poster_id' => $poster->id, 'risk_level' => 'moderate']);
        $this->post(route('admin.tasks.decision', $rejected), ['decision' => 'reject'])->assertSessionHasErrors('note');
        $this->post(route('admin.tasks.decision', $rejected), ['decision' => 'reject', 'note' => 'The task needs revision.'])->assertRedirect();
        $this->assertSame(TaskStatus::Rejected, $rejected->fresh()->status);

        $changes = Task::factory()->create(['poster_id' => $poster->id, 'risk_level' => 'moderate']);
        $this->post(route('admin.tasks.decision', $changes), ['decision' => 'request_changes'])->assertSessionHasErrors('note');
        $this->post(route('admin.tasks.decision', $changes), ['decision' => 'request_changes', 'note' => 'Please clarify the activities.'])->assertRedirect();
        $this->assertSame(TaskStatus::ChangesRequested, $changes->fresh()->status);

        $high = Task::factory()->create(['poster_id' => $poster->id, 'risk_level' => 'high']);
        $this->post(route('admin.tasks.decision', $high), ['decision' => 'approve'])->assertSessionHasErrors('note');
        $this->post(route('admin.tasks.decision', $high), ['decision' => 'approve', 'note' => 'Reviewed the workplace plan and approved the override.'])->assertRedirect();
        $this->assertSame(TaskStatus::Approved, $high->fresh()->status);
        $prohibited = Task::factory()->create(['poster_id' => $poster->id, 'risk_level' => 'low']);
        $prohibited->screenings()->create([
            'risk_level' => 'moderate', 'risk_score' => 2, 'factors' => [],
            'keyword_hits' => [['phrase' => 'take my exam', 'type' => 'prohibited', 'field' => 'title', 'start' => 0, 'length' => 12]],
            'hard_triggers' => [], 'outcome' => 'needs_review',
        ]);
        $this->post(route('admin.tasks.decision', $prohibited), ['decision' => 'approve'])->assertSessionHasErrors('note');
        $this->post(route('admin.tasks.decision', $prohibited), ['decision' => 'approve', 'note' => 'Reviewed and approved this exception.'])->assertRedirect();
        $this->assertDatabaseCount('admin_actions', 5);
    }

    public function test_admin_cannot_review_own_task_or_a_non_pending_task(): void
    {
        $admin = $this->verifiedPoster();
        $admin->forceFill(['is_admin' => true])->save();
        $ownTask = Task::factory()->create(['poster_id' => $admin->id]);
        $approved = Task::factory()->create(['poster_id' => $this->verifiedPoster()->id, 'status' => TaskStatus::Approved]);
        $nonAdmin = $this->verifiedPoster();

        $this->get(route('admin.tasks.show', $ownTask))->assertRedirect(route('login'));
        $this->post(route('admin.tasks.decision', $ownTask), ['decision' => 'reject', 'note' => 'No'])->assertRedirect(route('login'));
        $this->actingAs($nonAdmin)->get(route('admin.tasks.show', $ownTask))->assertForbidden();
        $this->post(route('admin.tasks.decision', $ownTask), ['decision' => 'reject', 'note' => 'No'])->assertForbidden();
        $this->actingAs($admin)->get(route('admin.tasks.show', $ownTask))->assertInertia(fn ($page) => $page->where('canReview', false));
        $this->post(route('admin.tasks.decision', $ownTask), ['decision' => 'reject', 'note' => 'No'])->assertForbidden();
        $this->post(route('admin.tasks.decision', $approved), ['decision' => 'reject', 'note' => 'No'])->assertForbidden();
    }

    public function test_admin_sees_risk_data_but_posters_do_not(): void
    {
        $admin = $this->verifiedPoster();
        $admin->forceFill(['is_admin' => true])->save();
        $poster = $this->verifiedPoster();
        $task = Task::factory()->create(['poster_id' => $poster->id, 'work_mode' => 'remote']);
        app(TaskModerationService::class)->screenAndApply($task);

        $this->actingAs($poster)->get(route('tasks.show', $task))->assertInertia(fn ($page) => $page
            ->missing('task.riskLevel')
            ->missing('task.riskScore')
            ->missing('task.decisionSource')
            ->missing('task.screenedAt'));
        $this->actingAs($admin)->get(route('admin.tasks.show', $task))->assertInertia(fn ($page) => $page
            ->has('screening.riskLevel')
            ->has('screening.riskScore')
            ->has('screeningHistory'));
        $this->assertSame(404, $this->get('/browse-tasks')->getStatusCode());
    }

    public function test_screening_history_rejects_updates_and_deletes(): void
    {
        $screening = TaskScreening::factory()->create();

        try {
            $screening->update(['risk_score' => 100]);
            $this->fail('A screening row should not be updateable.');
        } catch (\LogicException $exception) {
            $this->assertSame('Task screening history is immutable.', $exception->getMessage());
        }

        try {
            $screening->delete();
            $this->fail('A screening row should not be deletable.');
        } catch (\LogicException $exception) {
            $this->assertSame('Task screening history is immutable.', $exception->getMessage());
        }
    }
}
