<?php

namespace Tests\Feature;

use App\Enums\AgreementStatus;
use App\Enums\TaskStatus;
use App\Models\Agreement;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\DemoScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoScenarioSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_demo_scenario_seeder_creates_synthetic_workflow_states(): void
    {
        $this->app['env'] = 'local';
        $this->seed(DemoScenarioSeeder::class);
        $this->assertSame(0, User::where('email', 'not like', '%@example.test')->count());
        foreach ([TaskStatus::PendingReview, TaskStatus::ChangesRequested, TaskStatus::Approved, TaskStatus::Rejected, TaskStatus::Assigned, TaskStatus::InProgress, TaskStatus::Completed, TaskStatus::Removed, TaskStatus::Cancelled] as $status) {
            $this->assertTrue(Task::where('status', $status->value)->exists(), "Missing synthetic task state {$status->value}.");
        }
        $this->assertTrue(Agreement::where('status', AgreementStatus::Negotiating->value)->exists());
        $this->assertTrue(Agreement::where('status', AgreementStatus::Agreed->value)->exists());
        $this->assertTrue(Task::where('title', 'Scenario: High-risk escalated')->exists());
        $this->assertTrue(Task::where('title', 'Scenario: Please message me on Telegram')->exists());
    }

    public function test_local_demo_commands_require_force_and_reset_seeded_scenarios(): void
    {
        $this->app['env'] = 'local';
        $this->artisan('taskmate:demo-seed')->assertFailed();
        $this->artisan('taskmate:demo-seed', ['--force' => true])->assertSuccessful();
        $this->assertTrue(User::where('email', 'admin@example.test')->exists());
        $this->artisan('taskmate:reset-demo')->assertFailed();
        $this->artisan('taskmate:reset-demo', ['--force' => true])->assertSuccessful();
        $this->assertTrue(User::where('email', 'admin@example.test')->exists());
        $this->artisan('taskmate:purge-demo-data')->assertFailed();
        $this->artisan('taskmate:purge-demo-data', ['--force' => true])->assertSuccessful();
        $this->assertFalse(User::where('email', 'like', '%@example.test')->exists());
    }
}
