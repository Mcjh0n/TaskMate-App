<?php

namespace Tests\Feature;

use App\Enums\AgreementStatus;
use App\Enums\TaskStatus;
use App\Models\Agreement;
use App\Models\AgreementConfirmation;
use App\Models\AgreementMessage;
use App\Models\AgreementVersion;
use App\Models\Application;
use App\Models\Task;
use App\Models\User;
use App\Services\AgreementContentHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgreementRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_locked_agreement_page_loads_for_both_parties_and_is_denied_to_admins(): void
    {
        [$poster, $student, $admin, $task, $agreement] = $this->lockedAgreementScenario();
        $this->assertDatabaseCount('task_completions', 0);
        $this->assertDatabaseCount('payment_events', 0);
        $this->assertDatabaseCount('ratings', 0);
        $this->assertDatabaseCount('reports', 0);
        $this->assertDatabaseCount('content_flags', 0);

        $this->actingAs($poster)->get(route('agreements.show', $agreement))->assertOk();
        $this->actingAs($student)->get(route('agreements.show', $agreement))->assertOk();
        $this->actingAs($admin)->get(route('agreements.show', $agreement))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.agreements.show', $agreement))->assertOk();
    }

    public function test_task_page_renders_without_an_agreement_and_agreement_page_renders_while_negotiating(): void
    {
        [$poster, $student, , $task, $agreement] = $this->lockedAgreementScenario();

        $agreement->status = AgreementStatus::Negotiating;
        $agreement->locked_at = null;
        $agreement->save();
        $task->status = TaskStatus::Assigned;
        $task->save();

        $this->actingAs($poster)->get(route('tasks.show', $task))->assertOk();
        $this->actingAs($student)->get(route('agreements.show', $agreement))->assertOk();

        $agreement->delete();
        $this->actingAs($poster)->get(route('tasks.show', $task))->assertOk();
    }

    public function test_revised_negotiating_agreement_with_send_back_message_renders(): void
    {
        [$poster, $student, , , $agreement] = $this->lockedAgreementScenario();
        $agreement->status = AgreementStatus::Negotiating;
        $agreement->locked_at = null;
        $agreement->save();

        $revision = new AgreementVersion;
        $revision->agreement_id = $agreement->id;
        $revision->version_number = 2;
        $revision->proposed_by = $student->id;
        $revision->deliverables = 'A synthetic revised deliverable.';
        $revision->terms = 'Revised synthetic agreement terms.';
        $revision->final_amount = 550;
        $revision->start_date = today()->addDay();
        $revision->due_date = today()->addWeek();
        $revision->work_mode = 'online';
        $revision->payment_arrangement = 'Direct payment after completion.';
        $revision->comment = 'Synthetic revision for rendering coverage.';
        $revision->created_at = now();
        $revision->content_hash = app(AgreementContentHasher::class)->hash($revision);
        $revision->save();
        $agreement->current_version_id = $revision->id;
        $agreement->save();
        $message = new AgreementMessage;
        $message->agreement_id = $agreement->id;
        $message->user_id = $poster->id;
        $message->body = 'Please clarify this synthetic term.';
        $message->created_at = now();
        $message->save();

        $response = $this->actingAs($student)->get(route('agreements.show', $agreement))->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('agreement.currentVersion.versionNumber', 2)
            ->has('agreement.versions', 2)
            ->has('agreement.messages', 1));
    }

    public function test_task_state_pages_render_without_optional_related_records(): void
    {
        [$poster, , $admin] = $this->lockedAgreementScenario();

        foreach ([TaskStatus::PendingReview, TaskStatus::Approved, TaskStatus::Rejected, TaskStatus::Cancelled, TaskStatus::Removed] as $status) {
            $task = Task::factory()->for($poster, 'poster')->create(['status' => $status]);
            $this->actingAs($poster)->get(route('tasks.show', $task))->assertOk();
            $this->actingAs($admin)->get(route('admin.tasks.show', $task))->assertOk();
        }
    }

    private function lockedAgreementScenario(): array
    {
        $poster = User::factory()->verified()->facultyStaff()->create();
        $student = User::factory()->verified()->create();
        $admin = User::factory()->verified()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $task = Task::factory()->for($poster, 'poster')->approved()->create(['work_mode' => 'online', 'workplace_name' => null, 'workplace_address' => null, 'workplace_setting' => null]);
        $application = Application::factory()->for($task)->for($student, 'applicant')->accepted()->create();
        $agreement = Agreement::factory()->for($task)->for($application)->for($poster, 'poster')->for($student, 'student')->create([
            'status' => AgreementStatus::Agreed,
            'locked_at' => now(),
        ]);
        $version = new AgreementVersion;
        $version->agreement_id = $agreement->id;
        $version->version_number = 1;
        $version->proposed_by = $poster->id;
        $version->deliverables = 'A synthetic agreed deliverable.';
        $version->terms = 'Synthetic agreement terms.';
        $version->final_amount = 500;
        $version->start_date = today()->addDay();
        $version->due_date = today()->addWeek();
        $version->work_mode = 'online';
        $version->payment_arrangement = 'Direct payment after completion.';
        $version->created_at = now();
        $version->content_hash = app(AgreementContentHasher::class)->hash($version);
        $version->save();
        $agreement->current_version_id = $version->id;
        $agreement->save();
        foreach ([$poster, $student] as $party) {
            $confirmation = new AgreementConfirmation;
            $confirmation->agreement_version_id = $version->id;
            $confirmation->user_id = $party->id;
            $confirmation->confirmed_at = now();
            $confirmation->save();
        }
        $task->status = TaskStatus::InProgress;
        $task->save();

        return [$poster, $student, $admin, $task, $agreement];
    }
}
