<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Enums\VerificationStatus;
use App\Models\AdminAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_every_admin_route(): void
    {
        $user = User::factory()->create();

        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/admin/users')->assertRedirect(route('login'));
        $this->get("/admin/users/{$user->id}")->assertRedirect(route('login'));
        $this->post("/admin/users/{$user->id}/verification")->assertRedirect(route('login'));
    }

    public function test_non_admin_users_receive_forbidden_for_every_admin_route(): void
    {
        $user = User::factory()->create();

        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
        $this->get("/admin/users/{$user->id}")->assertForbidden();
        $this->post("/admin/users/{$user->id}/verification", ['decision' => 'verified'])->assertForbidden();
    }

    public function test_admin_dashboard_shows_account_counts_and_recent_actions(): void
    {
        $admin = $this->makeAdmin();
        $pending = User::factory()->create();
        $moreInfo = User::factory()->create(['verification_status' => VerificationStatus::MoreInfoRequested]);
        $verified = User::factory()->verified()->create();
        $rejected = User::factory()->create(['verification_status' => VerificationStatus::Rejected]);
        $moreInfo->adminActions()->create([
            'admin_id' => $admin->id,
            'action' => 'user_more_info_requested',
            'note' => 'Synthetic admin note.',
        ]);

        $this->actingAs($admin)->get('/admin')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->where('counts.pending', 1)
            ->where('counts.moreInfoRequested', 1)
            ->where('counts.verified', 2)
            ->where('counts.rejected', 1)
            ->where('recentActions.0.note', 'Synthetic admin note.')
            ->missing('recentActions.0.idNumber')
            ->missing('recentActions.0.supportingInfo'));
    }

    public function test_user_search_filters_pending_first_and_list_excludes_raw_identity_fields(): void
    {
        $admin = $this->makeAdmin();
        User::factory()->verified()->create([
            'name' => 'Synthetic Search Account',
            'email' => 'search@example.test',
            'user_type' => UserType::Organization,
            'id_number' => null,
            'affiliation' => null,
            'organization_name' => 'Synthetic Organization',
            'position' => 'Synthetic Manager',
            'supporting_info' => 'Private synthetic organization description.',
        ]);
        User::factory()->create([
            'name' => 'Synthetic Pending Search Account',
            'email' => 'pending.other@example.test',
        ]);

        $this->actingAs($admin)->get('/admin/users?search=Search%20Account')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/index')
                ->has('users.data', 2)
                ->where('users.data.0.verificationStatus', 'pending')
                ->where('users.data.1.verificationStatus', 'verified')
                ->missing('users.data.1.email')
                ->missing('users.data.1.idNumber')
                ->missing('users.data.1.supportingInfo'));

        $this->get('/admin/users?user_type=organization&verification_status=verified')
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.userType', 'organization'));

        $this->get('/admin/users?search=search%40example.test')
            ->assertInertia(fn (Assert $page) => $page
                ->has('users.data', 1)
                ->where('users.data.0.name', 'Synthetic Search Account')
                ->missing('users.data.0.email'));
    }

    public function test_user_list_is_paginated(): void
    {
        $admin = $this->makeAdmin();
        User::factory(16)->create();

        $this->actingAs($admin)->get('/admin/users')->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 15)
            ->where('users.total', 17)
            ->where('users.current_page', 1)
            ->has('users.links', 4));
    }

    public function test_admin_detail_includes_submitted_details_and_action_history(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create([
            'id_number' => 'FAKE-PRIVATE-001',
            'supporting_info' => 'Private synthetic organization description.',
            'verification_note' => 'Please clarify the organization details.',
        ]);
        $user->adminActions()->create([
            'admin_id' => $admin->id,
            'action' => 'user_more_info_requested',
            'note' => 'Please clarify the organization details.',
        ]);

        $this->actingAs($admin)->get("/admin/users/{$user->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/show')
                ->where('user.idNumber', 'FAKE-PRIVATE-001')
                ->where('user.supportingInfo', 'Private synthetic organization description.')
                ->where('user.verificationNote', 'Please clarify the organization details.')
                ->where('history.0.note', 'Please clarify the organization details.'));
    }

    public function test_approval_verifies_account_records_approver_and_adds_immutable_action(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create(['verification_note' => 'Old synthetic note.']);

        $this->actingAs($admin)->post("/admin/users/{$user->id}/verification", ['decision' => 'verified'])
            ->assertRedirect(route('admin.users.show', $user));

        $user->refresh();
        $this->assertSame(VerificationStatus::Verified, $user->verification_status);
        $this->assertNotNull($user->verified_at);
        $this->assertSame($admin->id, $user->verified_by);
        $this->assertNull($user->verification_note);
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $admin->id,
            'action' => 'user_verified',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'note' => null,
        ]);
        $this->assertSame('account.approved', $user->notifications()->firstOrFail()->data['event']);
        $this->assertSame('/notifications', $user->notifications()->first()->data['url']);
        $this->assertSame(0, $admin->notifications()->count());

        $action = AdminAction::query()->firstOrFail();
        try {
            $action->note = 'Changed synthetic audit note.';
            $action->save();
            $this->fail('Admin action records must not be updated.');
        } catch (\LogicException $exception) {
            $this->assertSame('Admin action records are insert-only.', $exception->getMessage());
        }

        $this->expectException(\LogicException::class);
        $action->delete();
    }

    public function test_rejection_and_more_info_request_require_notes_and_record_actions(): void
    {
        $admin = $this->makeAdmin();
        $rejected = User::factory()->create();
        $moreInfo = User::factory()->create();

        $this->actingAs($admin)->from("/admin/users/{$rejected->id}")
            ->post("/admin/users/{$rejected->id}/verification", ['decision' => 'rejected'])
            ->assertSessionHasErrors('note');
        $this->post("/admin/users/{$moreInfo->id}/verification", ['decision' => 'more_info_requested'])
            ->assertSessionHasErrors('note');

        $this->post("/admin/users/{$rejected->id}/verification", ['decision' => 'rejected', 'note' => 'Synthetic reason for rejection.'])
            ->assertRedirect();
        $this->post("/admin/users/{$moreInfo->id}/verification", ['decision' => 'more_info_requested', 'note' => 'Please add synthetic supporting details.'])
            ->assertRedirect();

        $this->assertSame(VerificationStatus::Rejected, $rejected->fresh()->verification_status);
        $this->assertSame('Synthetic reason for rejection.', $rejected->fresh()->verification_note);
        $this->assertSame(VerificationStatus::MoreInfoRequested, $moreInfo->fresh()->verification_status);
        $this->assertSame('Please add synthetic supporting details.', $moreInfo->fresh()->verification_note);
        $this->assertSame(2, AdminAction::query()->where('admin_id', $admin->id)->count());
        $this->assertSame('account.rejected', $rejected->notifications()->firstOrFail()->data['event']);
        $this->assertSame('account.more_info_requested', $moreInfo->notifications()->firstOrFail()->data['event']);
        $this->assertSame(0, $admin->notifications()->count());
    }

    public function test_admin_cannot_change_own_verification_status(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)->post("/admin/users/{$admin->id}/verification", [
            'decision' => 'rejected',
            'note' => 'Synthetic self-change attempt.',
        ])->assertForbidden();

        $this->assertSame(VerificationStatus::Verified, $admin->fresh()->verification_status);
        $this->assertDatabaseCount('admin_actions', 0);
    }

    public function test_account_owner_can_update_requested_details_without_changing_type_or_email(): void
    {
        $user = User::factory()->create([
            'email' => 'student@example.test',
            'user_type' => UserType::Student,
            'verification_status' => VerificationStatus::MoreInfoRequested,
            'verification_note' => 'Please update the synthetic affiliation.',
            'id_number' => 'FAKE-STU-404',
        ]);
        $admin = $this->makeAdmin();
        $user->adminActions()->create([
            'admin_id' => $admin->id,
            'action' => 'user_more_info_requested',
            'note' => 'Please update the synthetic affiliation.',
        ]);

        $this->actingAs($user)->put('/verification/details', [
            'id_number' => 'FAKE-STU-405',
            'affiliation' => 'Updated Synthetic College and Program',
            'user_type' => 'organization',
            'email' => 'changed@example.test',
        ])->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertSame(VerificationStatus::Pending, $user->verification_status);
        $this->assertNull($user->verification_note);
        $this->assertSame(UserType::Student, $user->user_type);
        $this->assertSame('student@example.test', $user->email);
        $this->assertSame('FAKE-STU-405', $user->id_number);
        $this->assertSame('Updated Synthetic College and Program', $user->affiliation);
        $this->assertDatabaseCount('admin_actions', 2);
        $this->assertDatabaseHas('admin_actions', [
            'admin_id' => $user->id,
            'action' => 'user_verification_details_updated',
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);
        $this->assertSame('account.resubmitted', $admin->notifications()->latest()->firstOrFail()->data['event']);
        $metadata = AdminAction::query()->where('action', 'user_verification_details_updated')->firstOrFail()->metadata;
        $this->assertSame(['actor_type' => 'user', 'fields' => ['id_number', 'affiliation']], $metadata);
    }

    public function test_owner_can_update_organization_details_but_not_other_account_types(): void
    {
        $user = User::factory()->create([
            'email' => 'organization@example.test',
            'user_type' => UserType::Organization,
            'verification_status' => VerificationStatus::Rejected,
            'affiliation' => null,
            'organization_name' => 'Old Synthetic Organization',
            'position' => 'Synthetic Representative',
            'supporting_info' => 'Old synthetic description.',
        ]);

        $this->actingAs($user)->put('/verification/details', [
            'organization_name' => 'Updated Synthetic Organization',
            'position' => 'Updated Synthetic Representative',
            'supporting_info' => 'Updated synthetic organization description.',
            'user_type' => 'student',
            'email' => 'changed@example.test',
        ])->assertRedirect(route('dashboard'));

        $this->assertSame(UserType::Organization, $user->fresh()->user_type);
        $this->assertSame('organization@example.test', $user->fresh()->email);
        $this->assertSame(VerificationStatus::Pending, $user->fresh()->verification_status);
        $this->assertSame('Updated Synthetic Organization', $user->fresh()->organization_name);
    }

    public function test_pending_accounts_cannot_use_the_resubmission_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/verification/details')->assertForbidden();
        $this->put('/verification/details', [
            'id_number' => 'FAKE-STU-001',
            'affiliation' => 'Synthetic College',
        ])->assertForbidden();
    }

    public function test_raw_registration_fields_are_not_shared_on_dashboard(): void
    {
        $user = User::factory()->create([
            'id_number' => 'FAKE-PRIVATE-999',
            'supporting_info' => 'Private synthetic description.',
        ]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('auth.user.id_number')
                ->missing('auth.user.supporting_info')
                ->missing('auth.user.verification_note')
                ->missing('adminActions'));
    }

    public function test_dashboard_exposes_only_the_current_user_verification_note(): void
    {
        $admin = $this->makeAdmin();
        $user = User::factory()->create([
            'verification_status' => VerificationStatus::Rejected,
            'verification_note' => 'Visible synthetic user note.',
        ]);
        $user->adminActions()->create([
            'admin_id' => $admin->id,
            'action' => 'user_rejected',
            'note' => 'Admin-only synthetic action note.',
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
            ->where('verificationNote', 'Visible synthetic user note.')
            ->missing('adminActions')
            ->missing('recentActions'));
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->verified()->create();
        $admin->is_admin = true;
        $admin->save();

        return $admin;
    }
}
