<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccountRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_verified_students_can_apply(): void
    {
        $student = User::factory()->verified()->create(['user_type' => UserType::Student]);
        $pendingStudent = User::factory()->create(['user_type' => UserType::Student]);
        $faculty = User::factory()->verified()->create(['user_type' => UserType::FacultyStaff]);
        $organization = User::factory()->verified()->create(['user_type' => UserType::Organization]);

        $this->assertTrue($student->canApply());
        $this->assertFalse($pendingStudent->canApply());
        $this->assertFalse($faculty->canApply());
        $this->assertFalse($organization->canApply());
    }

    public function test_only_verified_accounts_with_posting_eligibility_can_post(): void
    {
        config(['taskmate.students_can_post' => true]);

        $student = User::factory()->verified()->create(['user_type' => UserType::Student]);
        $faculty = User::factory()->verified()->create(['user_type' => UserType::FacultyStaff]);
        $organization = User::factory()->verified()->create(['user_type' => UserType::Organization]);
        $pendingFaculty = User::factory()->create(['user_type' => UserType::FacultyStaff]);

        $this->assertTrue($student->canPost());
        $this->assertTrue($faculty->canPost());
        $this->assertTrue($organization->canPost());
        $this->assertFalse($pendingFaculty->canPost());
    }

    public function test_student_posting_can_be_disabled_through_configuration(): void
    {
        config(['taskmate.students_can_post' => false]);
        $student = User::factory()->verified()->create(['user_type' => UserType::Student]);

        $this->assertFalse($student->canPost());
    }

    public function test_rejected_accounts_cannot_post_or_apply(): void
    {
        $student = User::factory()->create([
            'user_type' => UserType::Student,
            'verification_status' => 'rejected',
        ]);
        $organization = User::factory()->create([
            'user_type' => UserType::Organization,
            'verification_status' => 'rejected',
        ]);

        $this->assertTrue($student->isStudent());
        $this->assertFalse($student->canPost());
        $this->assertFalse($student->canApply());
        $this->assertTrue($organization->isOrganization());
        $this->assertFalse($organization->canPost());
        $this->assertFalse($organization->canApply());
    }
}
