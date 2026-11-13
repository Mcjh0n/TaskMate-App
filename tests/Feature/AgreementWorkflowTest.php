<?php

namespace Tests\Feature;

use App\Enums\AgreementStatus;
use App\Enums\ApplicationStatus;
use App\Enums\TaskStatus;
use App\Models\Agreement;
use App\Models\AgreementConfirmation;
use App\Models\AgreementVersion;
use App\Models\Application;
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
        $task = Task::factory()->assigned()->create(['poster_id' => $poster->id, 'deadline' => today()->addDays(10)]);
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
            'due_date' => today()->addDays(5)->toDateString(),
            'payment_arrangement' => 'The poster pays directly by bank transfer after the final files are accepted.',
            'comment' => 'Synthetic test proposal',
        ], $override);
    }

    private function createAgreement(array $pair): Agreement
    {
        $this->actingAs($pair['poster'])->post(route('agreements.store', $pair['task']), $this->proposalData())->assertRedirect();
        return Agreement::query()->firstOrFail()->load('currentVersion');
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

    public function test_initial_proposal_uses_disclosed_workplace_and_work_mode_and_prefills_amount(): void
    {
        $pair = $this->assignedTask();
        $pair['task']->update(['work_mode' => 'hybrid', 'workplace_name' => 'Synthetic campus library', 'workplace_address' => 'Synthetic campus address']);
        $this->actingAs($pair['poster'])->get(route('agreements.create', $pair['task']))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('agreements/create')->where('defaults.finalAmount', '1250.00')->where('defaults.workMode', 'hybrid'));

        $data = $this->proposalData(['work_mode' => 'remote', 'workplace_name' => 'Forged place', 'workplace_address' => 'Forged address']);
        $this->actingAs($pair['poster'])->post(route('agreements.store', $pair['task']), $data)->assertRedirect();
        $version = AgreementVersion::query()->firstOrFail();
        $this->assertSame('hybrid', $version->work_mode);
        $this->assertSame('Synthetic campus library', $version->workplace_name);
        $this->assertSame('Synthetic campus address', $version->workplace_address);
        $this->assertSame($pair['poster']->id, $version->confirmations()->sole()->user_id);
        $this->assertSame('1400.00', $version->final_amount);
    }

    public function test_remote_agreement_keeps_workplace_fields_null_and_work_mode_fixed(): void
    {
        $pair = $this->assignedTask();
        $pair['task']->update(['work_mode' => 'remote', 'workplace_name' => null, 'workplace_address' => null]);
        $this->actingAs($pair['poster'])->post(route('agreements.store', $pair['task']), $this->proposalData(['work_mode' => 'onsite', 'workplace_name' => 'Should be ignored']))->assertRedirect();
        $version = AgreementVersion::query()->firstOrFail();
        $this->assertSame('remote', $version->work_mode);
        $this->assertNull($version->workplace_name);
        $this->assertNull($version->workplace_address);
    }

    public function test_invalid_proposal_fields_are_rejected(): void
    {
        $pair = $this->assignedTask();
        $data = $this->proposalData(['terms' => str_repeat('x', 3001), 'final_amount' => 0, 'due_date' => today()->toDateString(), 'payment_arrangement' => '']);
        $this->actingAs($pair['poster'])->post(route('agreements.store', $pair['task']), $data)->assertSessionHasErrors(['terms', 'final_amount', 'due_date', 'payment_arrangement']);
        $this->assertDatabaseCount('agreements', 0);
    }

    public function test_revision_is_insert_only_resets_confirmations_and_confirms_the_proposer(): void
    {
        $pair = $this->assignedTask();
        $agreement = $this->createAgreement($pair);
        $first = $agreement->currentVersion;
        $this->actingAs($pair['poster'])->post(route('agreements.revise', $agreement), $this->proposalData(['version_id' => $first->id, 'terms' => 'Updated terms', 'comment' => 'Please review this change.']))->assertRedirect();

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
        $this->assertSame(1, $agreement->versions()->count());
    }

    public function test_either_party_can_cancel_before_lock_and_task_and_accepted_application_are_cancelled(): void
    {
        $pair = $this->assignedTask();
        $agreement = $this->createAgreement($pair);
        $this->actingAs($pair['student'])->post(route('agreements.cancel', $agreement), [])->assertSessionHasErrors('reason');
        $this->actingAs($pair['student'])->post(route('agreements.cancel', $agreement), ['reason' => 'The dates no longer work.'])->assertRedirect();
        $this->assertSame(AgreementStatus::Cancelled, $agreement->fresh()->status);
        $this->assertSame(TaskStatus::Cancelled, $pair['task']->fresh()->status);
        $this->assertSame(ApplicationStatus::Cancelled, $pair['application']->fresh()->status);
        $this->assertSame($pair['student']->id, $agreement->fresh()->cancelled_by);
        $this->assertFalse($pair['poster']->can('cancel', $pair['task']->fresh()));
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
