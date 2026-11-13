<?php

namespace Tests\Feature;

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
    }

    public function test_admin_middleware_forbids_non_admin_users(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/test/admin-only')
            ->assertForbidden();
    }

    public function test_admin_middleware_allows_admin_users(): void
    {
        $user = User::factory()->create();
        $user->is_admin = true;
        $user->save();

        $this->actingAs($user)->get('/test/admin-only')->assertOk();
    }

    public function test_account_verified_middleware_redirects_unverified_users_with_a_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/test/verified-only')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', 'Your account is awaiting verification by an administrator.');
    }

    public function test_account_verified_middleware_allows_verified_users(): void
    {
        $user = User::factory()->create();
        $user->verification_status = 'verified';
        $user->save();

        $this->actingAs($user)->get('/test/verified-only')->assertOk();
    }
}
