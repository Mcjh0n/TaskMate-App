<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AccountMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth', 'admin'])->get('/test/admin-only', fn () => 'ok');
        Route::middleware(['auth', 'account.verified'])->get('/test/verified-only', fn () => 'ok');
        Route::middleware(['auth', 'can.post'])->get('/test/can-post', fn () => 'ok');
        Route::middleware(['auth', 'can.apply'])->get('/test/can-apply', fn () => 'ok');
    }

    public function test_admin_middleware_redirects_guests_and_forbids_non_admin_users(): void
    {
        $this->get('/test/admin-only')->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get('/test/admin-only')->assertForbidden();
    }

    public function test_admin_middleware_allows_admin_users(): void
    {
        $admin = User::factory()->create();
        $admin->is_admin = true;
        $admin->save();

        $this->actingAs($admin)->get('/test/admin-only')->assertOk();
    }

    public function test_account_verified_middleware_redirects_unverified_users_with_a_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/test/verified-only')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', 'An administrator must verify your TaskMate account before you can use this feature.');
    }

    public function test_account_verified_middleware_blocks_each_unverified_status(): void
    {
        foreach (['pending', 'rejected', 'more_info_requested'] as $status) {
            $user = User::factory()->create(['verification_status' => $status]);
            $this->actingAs($user)
                ->get('/test/verified-only')
                ->assertRedirect(route('dashboard'))
                ->assertSessionHas('status', 'An administrator must verify your TaskMate account before you can use this feature.');
        }
    }

    public function test_account_verified_middleware_allows_verified_users(): void
    {
        $user = User::factory()->verified()->create();

        $this->actingAs($user)->get('/test/verified-only')->assertOk();
    }

    public function test_account_verified_middleware_obeys_configuration(): void
    {
        config(['taskmate.require_verification' => false]);

        $this->actingAs(User::factory()->create())->get('/test/verified-only')->assertOk();
    }

    public function test_can_post_middleware_checks_type_status_configuration_and_admin_flag(): void
    {
        config(['taskmate.students_can_post' => true]);

        $this->actingAs(User::factory()->verified()->create(['user_type' => UserType::Student]))
            ->get('/test/can-post')->assertOk();
        $this->actingAs(User::factory()->verified()->create(['user_type' => UserType::FacultyStaff]))
            ->get('/test/can-post')->assertOk();
        $this->actingAs(User::factory()->verified()->create(['user_type' => UserType::Organization]))
            ->get('/test/can-post')->assertOk();

        foreach ([
            User::factory()->create(['user_type' => UserType::Student]),
            User::factory()->create(['user_type' => UserType::FacultyStaff]),
            User::factory()->create(['user_type' => UserType::Organization]),
            User::factory()->create(['user_type' => UserType::Student, 'verification_status' => 'rejected']),
            User::factory()->create(['user_type' => UserType::FacultyStaff, 'verification_status' => 'rejected']),
            User::factory()->create(['user_type' => UserType::Organization, 'verification_status' => 'rejected']),
            User::factory()->create(['user_type' => UserType::Student, 'verification_status' => 'more_info_requested']),
            User::factory()->create(['user_type' => UserType::FacultyStaff, 'verification_status' => 'more_info_requested']),
            User::factory()->create(['user_type' => UserType::Organization, 'verification_status' => 'more_info_requested']),
        ] as $user) {
            $this->actingAs($user)->get('/test/can-post')->assertForbidden();
        }

        $admin = User::factory()->verified()->create(['user_type' => UserType::FacultyStaff]);
        $admin->is_admin = true;
        $admin->save();
        $this->actingAs($admin)->get('/test/can-post')->assertForbidden();

        config(['taskmate.students_can_post' => false]);
        $student = User::factory()->verified()->create(['user_type' => UserType::Student]);
        $this->actingAs($student)->get('/test/can-post')->assertForbidden();
    }

    public function test_can_apply_middleware_allows_only_verified_students(): void
    {
        $this->actingAs(User::factory()->verified()->create(['user_type' => UserType::Student]))
            ->get('/test/can-apply')->assertOk();

        foreach ([
            User::factory()->create(['user_type' => UserType::Student]),
            User::factory()->create(['user_type' => UserType::Student, 'verification_status' => 'rejected']),
            User::factory()->create(['user_type' => UserType::Student, 'verification_status' => 'more_info_requested']),
            User::factory()->verified()->create(['user_type' => UserType::FacultyStaff]),
            User::factory()->verified()->create(['user_type' => UserType::Organization]),
            User::factory()->create(['user_type' => UserType::FacultyStaff]),
            User::factory()->create(['user_type' => UserType::Organization]),
            User::factory()->create(['user_type' => UserType::FacultyStaff, 'verification_status' => 'rejected']),
            User::factory()->create(['user_type' => UserType::Organization, 'verification_status' => 'rejected']),
            User::factory()->create(['user_type' => UserType::FacultyStaff, 'verification_status' => 'more_info_requested']),
            User::factory()->create(['user_type' => UserType::Organization, 'verification_status' => 'more_info_requested']),
        ] as $user) {
            $this->actingAs($user)->get('/test/can-apply')->assertForbidden();
        }

        $admin = User::factory()->verified()->create(['user_type' => UserType::Student]);
        $admin->is_admin = true;
        $admin->save();
        $this->actingAs($admin)->get('/test/can-apply')->assertForbidden();
    }
}
