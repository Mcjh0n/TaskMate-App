<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LocalTaskMateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalTaskMateSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_seed_data_uses_synthetic_examples_and_expected_statuses(): void
    {
        $this->seed(LocalTaskMateSeeder::class);
        $this->seed(LocalTaskMateSeeder::class);

        $this->assertDatabaseCount('users', 7);
        $this->assertDatabaseCount('tasks', 12);
        $this->assertDatabaseCount('applications', 3);
        $this->assertDatabaseCount('conversations', 3);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.test',
            'is_admin' => true,
            'verification_status' => 'verified',
        ]);
        $this->assertSame(2, User::query()->where('user_type', 'student')->where('verification_status', 'verified')->count());
        $this->assertDatabaseHas('users', [
            'email' => 'student.pending@example.test',
            'verification_status' => 'pending',
            'id_number' => 'FAKE-STU-003',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'organization@example.test',
            'user_type' => 'organization',
            'verification_status' => 'verified',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'organization.pending@example.test',
            'user_type' => 'organization',
            'verification_status' => 'pending',
        ]);
        $this->assertTrue(config('taskmate.students_can_post'));
        $this->assertTrue(config('taskmate.require_verification'));
    }
}
