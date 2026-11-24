<?php

namespace Tests\Feature;

use App\Enums\AgreementStatus;
use App\Enums\ApplicationStatus;
use App\Enums\RatingDirection;
use App\Enums\TaskStatus;
use App\Models\Agreement;
use App\Models\AgreementVersion;
use App\Models\Application;
use App\Models\Rating;
use App\Models\Report;
use App\Models\Task;
use App\Models\User;
use App\Services\AgreementContentHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgreementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function assignedTask(): array
    {
        $poster = User::factory()->verified()->facultyStaff()->create();
        $student = User::factory()->verified()->create();
        $outsider = User::factory()->verified()->create();
        $task = Task::factory()->assigned()->create(['poster_id' => $poster->id, 'estimated_duration_value' => 3, 'estimated_duration_unit' => 'days']);
        $application = Application::factory()->accepted()->create([
            'task_id' => $task->id,
            'applicant_id' => $student->id,
            'proposed_amount' => 1250,
        ]);

        return compact('poster', 'student', 'outsider', 'task', 'application');
    }

    private function proposalData(array $override = []): array
    {
        return array_merge([
            'deliverables' => 'Three edited poster designs',
            'terms' => 'The student submits drafts, and the poster gives feedback within one day.',
            'final_amount' => '1400.00',
            'start_date' => today()->addDay()->toDateString(),
            'payment_arrangement' => 'The poster pays directly by bank transfer after the final files are accepted.',
            'comment' => 'Synthetic test proposal',
        ], $override);
    }

    private function createAgreement(array $pair): Agreement
    {
        $this->actingAs($pair['poster'])->get(route('agreements.create', $pair['task']))->assertRedirect();
        $agreement = Agreement::query()->where('task_id', $pair['task']->id)->firstOrFail();
        $this->post(route('agreements.store', $pair['task']))->assertRedirect();

        return Agreement::query()->where('task_id', $pair['task']->id)->firstOrFail()->load('currentVersion');
    }

    public function test_only_task_poster_can_propose_for_an_assigned_task_with_one_accepted_application(): void
    {
        $pair = $this->assignedTask();
        $this->actingAs($pair['student'])->post(route('agreements.store', $pair['task']), $this->proposalData())->assertForbidden();
        $this->actingAs($pair['outsider'])->post(route('agreements.store', $pair['task']), $this->proposalData())->assertForbidden();

        $task = Task::factory()->approved()->create(['poster_id' => $pair['poster']->id]);
        $this->actingAs($pair['poster'])->post(route('agreements.store', $task), $this->proposalData())->assertForbidden();
        $second = new Application;
        $second->task_id = $pair['task']->id;
        $second->applicant_id = $pair['outsider']->id;
        $second->message = 'A second accepted application';
        $second->status = ApplicationStatus::Accepted;
        $second->responded_at = now();
        $second->save();
        $this->actingAs($pair['poster'])->post(route('agreements.store', $pair['task']), $this->proposalData())->assertForbidden();
        $this->assertDatabaseCount('agreements', 0);
    }

    public function test_acceptance_prepares_agreement_and_poster_sends_the_prefilled_version(): void
    {
        $pair = $this->assignedTask();
        $pair['task']->update(['work_mode' => 'off_campus', 'workplace_name' => 'Synthetic campus library', 'workplace_address' => 'Synthetic campus address', 'deliverables' => 'Three edited poster designs']);
        $this->actingAs($pair['poster'])->get(route('agreements.create', $pair['task']))
            ->assertRedirect();
        $agreement = Agreement::query()->where('task_id', $pair['task']->id)->firstOrFail()->load('currentVersion');
        $version = $agreement->currentVersion;
        $this->assertSame(AgreementStatus::Draft, $agreement->status);
        $this->assertSame('Three edited poster designs', $version->deliverables);
        $this->assertSame('1250.00', $version->final_amount);
        $this->assertNull($version->due_date);
        $this->assertSame(3, $version->duration_value);
        $this->assertSame('days', $version->duration_unit);
        $this->assertSame('off_campus', $version->work_mode);
        $this->assertSame('Synthetic campus library', $version->workplace_name);
        $this->assertSame('Synthetic campus address', $version->workplace_address);
        $this->assertSame(0, $version->confirmations()->count());
        $this->actingAs($pair['poster'])->get(route('agreements.show', $agreement))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('agreement.status', 'draft')->where('agreement.currentVersion.deliverables', 'Three edited poster designs')->where('agreement.currentVersion.durationLabel', '3 days')->missing('agreement.currentVersion.dueDate'));
        $this->actingAs($pair['student'])->get(route('agreements.show', $agreement))->assertForbidden();

        $this->actingAs($pair['poster'])->post(route('agreements.store', $pair['task']))->assertRedirect();
        $agreement->refresh();
        $version = $agreement->currentVersion()->firstOrFail();
        $this->assertSame(AgreementStatus::Negotiating, $agreement->status);
        $this->assertSame('off_campus', $version->work_mode);
        $this->assertSame('Synthetic campus library', $version->workplace_name);
        $this->assertSame('Synthetic campus address', $version->workplace_address);
        $this->assertSame($pair['poster']->id, $version->confirmations()->sole()->user_id);
        $this->assertSame('1250.00', $version->final_amount);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $pair['student']->id, 'data->event' => 'agreement.proposed']);
        $this->actingAs($pair['student'])->get(route('agreements.show', $agreement))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('agreement.currentVersion.finalAmount', '1250.00'));
    }

    public function test_remote_agreement_keeps_workplace_fields_null_and_work_mode_fixed(): void
    {
        $pair = $this->assignedTask();
        $pair['task']->update(['work_mode' => 'online', 'workplace_name' => null, 'workplace_address' => null]);
        $this->actingAs($pair['poster'])->get(route('agreements.create', $pair['task']))->assertRedirect();
        $this->post(route('agreements.store', $pair['task']))->assertRedirect();
        $version = AgreementVersion::query()->firstOrFail();
        $this->assertSame('online', $version->work_mode);
        $this->assertNull($version->workplace_name);
        $this->assertNull($version->workplace_address);
    }

    public function test_agreement_snapshots_minute_duration_and_hashes_the_unit(): void
    {
        $pair = $this->assignedTask();
        $pair['task']->update(['estimated_duration_value' => 45, 'estimated_duration_unit' => 'minutes']);

        $this->actingAs($pair['poster'])->get(route('agreements.create', $pair['task']))->assertRedirect();
        $version = AgreementVersion::query()->firstOrFail();

        $this->assertSame(45, $version->duration_value);
        $this->assertSame('minutes', $version->duration_unit);
        $this->assertSame('45 minutes', $version->durationLabel());
        $this->assertSame($version->content_hash, app(AgreementContentHasher::class)->hash($version));
    }

    public function test_applicant_amount_and_task_budget_are_used_without_a_second_manual_proposal_form(): void
    {
        $pair = $this->assignedTask();
        $this->actingAs($pair['poster'])->get(route('agreements.create', $pair['task']))->assertRedirect();
        $agreement = Agreement::query()->where('task_id', $pair['task']->id)->firstOrFail();
        $this->assertSame('1250.00', $agreement->currentVersion->final_amount);
        $this->assertNull($agreement->currentVersion->due_date);
        $this->assertSame(3, $agreement->currentVersion->duration_value);
        $this->assertSame('days', $agreement->currentVersion->duration_unit);
        $this->assertNotEmpty($agreement->currentVersion->terms);
        $this->assertNotEmpty($agreement->currentVersion->payment_arrangement);
        $this->actingAs($pair['poster'])->post(route('agreements.store', $pair['task']), ['final_amount' => '1', 'terms' => 'forged'])->assertRedirect();
        $agreement->refresh();
        $this->assertSame('1250.00', $agreement->currentVersion->final_amount);
        $this->assertNotSame('forged', $agreement->currentVersion->terms);
    }

    public function test_task_budget_is_used_when_applicant_did_not_suggest_an_amount(): void
    {
        $pair = $this->assignedTask();
        $pair['task']->update(['budget' => 2400]);
        $pair['application']->update(['proposed_amount' => null]);

        $this->actingAs($pair['poster'])->get(route('agreements.create', $pair['task']))->assertRedirect();
        $agreement = Agreement::query()->where('task_id', $pair['task']->id)->with('currentVersion')->firstOrFail();
        $this->assertSame('2400.00', $agreement->currentVersion->final_amount);
    }

    public function test_revision_is_insert_only_resets_confirmations_and_confirms_the_proposer(): void
    {
        $pair = $this->assignedTask();
        $agreement = $this->createAgreement($pair);
        $first = $agreement->currentVersion;
        $this->actingAs($pair['poster'])->post(route('agreements.revise', $agreement), $this->proposalData(['version_id' => $first->id, 'terms' => 'Updated terms', 'comment' => 'Please review this change.']))->assertRedirect();
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $pair['student']->id, 'data->event' => 'agreement.revised']);

        $agreement->refresh();
        $this->assertSame(2, $agreement->versions()->count());
        $this->assertSame([$pair['poster']->id], $first->confirmations()->pluck('user_id')->all());
        $this->assertSame([$pair['poster']->id], $agreement->currentVersion->confirmations()->pluck('user_id')->all());
        $this->assertSame('Updated terms', $agreement->currentVersion->terms);

        $current = $agreement->currentVersion;
        $this->actingAs($pair['student'])->post(route('agreements.revise', $agreement), $this->proposalData(['version_id' => $current->id, 'terms' => 'Student revision']))->assertRedirect();
        $agreement->refresh();
        $this->assertSame(3, $agreement->versions()->count());
        $this->assertSame([$pair['student']->id], $agreement->currentVersion->confirmations()->pluck('user_id')->all());
    }

    public function test_stale_confirmation_and_revision_are_refused(): void
    {
        $pair = $this->assignedTask();
        $agreement = $this->createAgreement($pair);
        $oldVersion = $agreement->current_version_id;
        $this->actingAs($pair['poster'])->post(route('agreements.revise', $agreement), $this->proposalData(['version_id' => $oldVersion, 'terms' => 'New current terms']))->assertRedirect();
        $this->actingAs($pair['student'])->post(route('agreements.confirm', $agreement), ['version_id' => $oldVersion])->assertInvalid('version_id');
        $this->actingAs($pair['student'])->post(route('agreements.revise', $agreement), $this->proposalData(['version_id' => $oldVersion]))->assertInvalid('version_id');
    }

    public function test_second_party_confirmation_locks_same_version_hashes_it_and_starts_work(): void
    {
        $pair = $this->assignedTask();
        $waiting = User::factory()->verified()->create();
        $waitingApplication = Application::factory()->create(['task_id' => $pair['task']->id, 'applicant_id' => $waiting->id]);
        $agreement = $this->createAgreement($pair);
        $version = $agreement->currentVersion;
        $this->actingAs($pair['student'])->post(route('agreements.confirm', $agreement), ['version_id' => $version->id])->assertRedirect()->assertSessionDoesntHaveErrors();

        $agreement->refresh();
        $version->refresh();
        $this->assertSame(AgreementStatus::Agreed, $agreement->status);
        $this->assertNotNull($agreement->locked_at);
        $this->assertSame(TaskStatus::InProgress, $pair['task']->fresh()->status);
        $this->assertSame($version->content_hash, app(AgreementContentHasher::class)->hash($version));
        $this->assertDatabaseCount('agreement_confirmations', 2);
        $this->assertSame(ApplicationStatus::Declined, $waitingApplication->fresh()->status);
        $this->assertNotNull($waitingApplication->fresh()->responded_at);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $pair['poster']->id, 'data->event' => 'agreement.confirmed']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $pair['poster']->id, 'data->event' => 'agreement.locked']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $waiting->id, 'data->event' => 'application.declined']);
        $this->assertDatabaseMissing('notifications', ['notifiable_id' => $pair['student']->id, 'data->event' => 'agreement.locked']);
    }

    public function test_locked_agreement_refuses_revision_confirmation_and_cancellation(): void
    {
        $pair = $this->assignedTask();
        $agreement = $this->createAgreement($pair);
        $version = $agreement->currentVersion;
        $this->actingAs($pair['student'])->post(route('agreements.confirm', $agreement), ['version_id' => $version->id])->assertRedirect()->assertSessionDoesntHaveErrors();
        $this->actingAs($pair['poster'])->post(route('agreements.confirm', $agreement), ['version_id' => $version->id])->assertForbidden();
        $this->actingAs($pair['poster'])->post(route('agreements.revise', $agreement), $this->proposalData(['version_id' => $version->id]))->assertForbidden();
        $this->actingAs($pair['student'])->post(route('agreements.cancel', $agreement), ['reason' => 'No longer needed'])->assertForbidden();
        $this->actingAs($pair['poster'])->put(route('agreements.show', $agreement), ['terms' => 'attempted edit'])->assertMethodNotAllowed();
        $this->assertFalse($pair['poster']->can('update', $pair['task']->fresh()));
        $this->assertFalse($pair['poster']->can('cancel', $pair['task']->fresh()));
        $this->assertSame(1, AgreementVersion::query()->count());
    }

    public function test_send_back_is_saved_as_an_append_only_message(): void
    {
        $pair = $this->assignedTask();
        $agreement = $this->createAgreement($pair);
        $version = $agreement->currentVersion;
        $this->actingAs($pair['student'])->post(route('agreements.send-back', $agreement), ['version_id' => $version->id, 'body' => 'Please clarify when drafts will be reviewed.'])->assertRedirect();
        $this->assertDatabaseHas('agreement_messages', ['agreement_id' => $agreement->id, 'user_id' => $pair['student']->id, 'body' => 'Please clarify when drafts will be reviewed.']);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $pair['poster']->id, 'data->event' => 'agreement.sent_back']);
        $this->assertSame(1, $agreement->versions()->count());
    }

    public function test_either_party_can_cancel_before_lock_reopens_the_task_and_cancels_only_the_accepted_application(): void
    {
        $pair = $this->assignedTask();
        $waiting = User::factory()->verified()->create();
        $waitingApplication = Application::factory()->create(['task_id' => $pair['task']->id, 'applicant_id' => $waiting->id]);
        $agreement = $this->createAgreement($pair);
        $this->actingAs($pair['outsider'])->post(route('agreements.cancel', $agreement), ['reason' => 'Not a party.'])->assertForbidden();
        $this->actingAs($pair['student'])->post(route('agreements.cancel', $agreement), [])->assertSessionHasErrors('reason');
        $this->actingAs($pair['student'])->post(route('agreements.cancel', $agreement), ['reason' => 'The dates no longer work.'])
            ->assertRedirect(route('applications.mine'))
            ->assertSessionHas('status', 'The agreement was cancelled. Your application is closed, and the task is open again for other applicants.');
        $this->assertSame(AgreementStatus::Cancelled, $agreement->fresh()->status);
        $this->assertSame(TaskStatus::Approved, $pair['task']->fresh()->status);
        $this->assertSame(ApplicationStatus::Cancelled, $pair['application']->fresh()->status);
        $this->assertSame(ApplicationStatus::Pending, $waitingApplication->fresh()->status);
        $this->assertSame($pair['student']->id, $agreement->fresh()->cancelled_by);
        $this->assertTrue($pair['poster']->can('cancel', $pair['task']->fresh()));
        $this->actingAs($pair['poster'])->get(route('agreements.show', $agreement))->assertInertia(fn (Assert $page) => $page->where('agreement.cancellationReason', 'The dates no longer work.'));
        $this->actingAs($pair['student'])->post(route('applications.store', $pair['task']), ['message' => 'Trying to reapply.', 'work_details_confirmed' => '1'])->assertForbidden();
        $this->actingAs($pair['poster'])->post(route('agreements.cancel', $agreement), ['reason' => 'A duplicate cancel.'])->assertForbidden();
        $this->assertSame('The dates no longer work.', $agreement->fresh()->cancellation_reason);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $pair['poster']->id, 'data->event' => 'agreement.cancelled']);
        if ($pair['student']->id !== $agreement->fresh()->cancelled_by) {
            $this->assertDatabaseHas('notifications', ['notifiable_id' => $pair['student']->id, 'data->event' => 'application.cancelled']);
        } else {
            $this->assertDatabaseMissing('notifications', ['notifiable_id' => $pair['student']->id, 'data->event' => 'application.cancelled']);
        }
    }

    public function test_after_either_party_cancels_the_poster_can_accept_another_applicant_and_start_a_fresh_agreement(): void
    {
        foreach (['poster', 'student'] as $cancellingParty) {
            $pair = $this->assignedTask();
            $agreement = $this->createAgreement($pair);
            $otherApplicant = User::factory()->verified()->create();
            Application::factory()->create(['task_id' => $pair['task']->id, 'applicant_id' => $otherApplicant->id]);

            $cancelResponse = $this->actingAs($pair[$cancellingParty])->post(route('agreements.cancel', $agreement), ['reason' => 'The original dates no longer work.']);
            $cancelResponse->assertRedirect($cancellingParty === 'poster' ? route('tasks.show', $pair['task']) : route('applications.mine'));
            $pending = Application::query()->where('task_id', $pair['task']->id)->where('applicant_id', $otherApplicant->id)->firstOrFail();
            $this->actingAs($pair['poster'])->post(route('tasks.applications.accept', [$pair['task'], $pending]))->assertRedirect();
            $this->assertSame(ApplicationStatus::Accepted, $pending->fresh()->status);
            $this->assertSame(TaskStatus::Assigned, $pair['task']->fresh()->status);
            $this->actingAs($pair['poster'])->get(route('agreements.create', $pair['task']))->assertRedirect();
            $this->post(route('agreements.store', $pair['task']))->assertRedirect();
            $this->assertSame(2, Agreement::query()->where('task_id', $pair['task']->id)->count());
            $this->assertSame(AgreementStatus::Negotiating, Agreement::query()->where('task_id', $pair['task']->id)->latest('id')->firstOrFail()->status);
            $this->assertSame(AgreementStatus::Cancelled, $agreement->fresh()->status);
        }
    }

    public function test_cancelling_without_waiting_applicants_keeps_task_approved_and_open_for_new_applications(): void
    {
        $pair = $this->assignedTask();
        $agreement = $this->createAgreement($pair);
        $this->actingAs($pair['poster'])->post(route('agreements.cancel', $agreement), ['reason' => 'The original dates changed.'])
            ->assertRedirect(route('tasks.show', $pair['task']));
        $this->assertSame(TaskStatus::Approved, $pair['task']->fresh()->status);

        $newStudent = User::factory()->verified()->create();
        $this->actingAs($newStudent)->get(route('browse.index'))->assertInertia(fn (Assert $page) => $page
            ->where('tasks.data.0.id', $pair['task']->id));
        $this->post(route('applications.store', $pair['task']), ['message' => 'I am interested in this reopened task.', 'work_details_confirmed' => '1'])->assertRedirect();
        $this->assertDatabaseHas('applications', ['task_id' => $pair['task']->id, 'applicant_id' => $newStudent->id, 'status' => ApplicationStatus::Pending->value]);
    }

    public function test_student_can_post_review_applicants_and_complete_an_agreement_while_applying_elsewhere(): void
    {
        $studentPoster = User::factory()->verified()->create();
        $worker = User::factory()->verified()->create();
        $task = Task::factory()->approved()->for($studentPoster, 'poster')->create([
            'work_mode' => 'online', 'workplace_name' => null, 'workplace_address' => null, 'workplace_setting' => null,
        ]);
        $otherPoster = User::factory()->verified()->facultyStaff()->create();
        $otherTask = Task::factory()->approved()->for($otherPoster, 'poster')->create();

        $this->actingAs($worker)->post(route('applications.store', $task), [
            'message' => 'I can complete this synthetic task.', 'work_details_confirmed' => '1',
        ])->assertRedirect();
        $application = Application::query()->where('task_id', $task->id)->firstOrFail();
        $this->actingAs($studentPoster)->get(route('tasks.mine'))->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.can_post_tasks', true)
            ->where('tasks.data.0.id', $task->id));
        $this->actingAs($studentPoster)->get(route('tasks.show', $task))->assertInertia(fn (Assert $page) => $page
            ->where('canProposeAgreement', false)
            ->where('applicants.0.applicant.id', $worker->id));
        $this->post(route('tasks.applications.accept', [$task, $application]))->assertRedirect();
        $this->assertSame(TaskStatus::Assigned, $task->fresh()->status);
        $this->get(route('agreements.create', $task))->assertRedirect();
        $this->post(route('agreements.store', $task))->assertRedirect();
        $agreement = Agreement::query()->where('task_id', $task->id)->firstOrFail();
        $this->actingAs($worker)->post(route('agreements.confirm', $agreement), ['version_id' => $agreement->current_version_id])->assertRedirect();
        $this->assertSame(TaskStatus::InProgress, $task->fresh()->status);
        $this->actingAs($studentPoster)->post(route('agreements.completion.complete', $agreement), ['note' => 'Synthetic completion.'])->assertRedirect();
        $this->assertSame(TaskStatus::Completed, $task->fresh()->status);

        $this->actingAs($worker)->post(route('agreements.rate.store', $agreement), [
            'overall_experience' => 4, 'comment' => 'Clear synthetic work brief.',
        ])->assertRedirect();
        $rating = Rating::query()->where('agreement_id', $agreement->id)->firstOrFail();
        $this->assertSame($studentPoster->id, $rating->ratee_id);
        $this->assertSame(RatingDirection::StudentToPoster, $rating->direction);

        $this->post(route('agreements.problem-reports.store', $agreement), [
            'type' => 'misleading_description', 'description' => 'Synthetic report about the disclosed task details.',
        ])->assertRedirect();
        $this->assertSame($studentPoster->id, Report::query()->where('agreement_id', $agreement->id)->firstOrFail()->reported_user_id);

        $this->actingAs($studentPoster)->post(route('applications.store', $otherTask), [
            'message' => 'I can also apply to this separate synthetic task.', 'work_details_confirmed' => '1',
        ])->assertRedirect();
        $this->assertDatabaseHas('applications', ['task_id' => $otherTask->id, 'applicant_id' => $studentPoster->id]);
    }

    public function test_agreement_pages_are_private_admin_is_read_only_and_props_exclude_private_fields(): void
    {
        $pair = $this->assignedTask();
        $agreement = $this->createAgreement($pair);
        $this->actingAs($pair['outsider'])->get(route('agreements.show', $agreement))->assertForbidden();
        auth()->logout();
        $this->get(route('agreements.show', $agreement))->assertRedirect(route('login'));
        $this->actingAs($pair['student'])->get(route('agreements.show', $agreement))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('agreements/show')
                ->missing('agreement.poster.email')->missing('agreement.poster.id_number')
                ->missing('agreement.student.email')->missing('agreement.student.id_number')
                ->has('agreement.currentVersion.confirmations', 1));

        $admin = User::factory()->verified()->create(['is_admin' => true]);
        $this->actingAs($admin)->get(route('admin.agreements.show', $agreement))->assertOk()->assertInertia(fn (Assert $page) => $page->component('admin/agreements/show'));
        $this->actingAs($admin)->post(route('agreements.confirm', $agreement), ['version_id' => $agreement->current_version_id])->assertForbidden();
    }
}
