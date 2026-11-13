<?php

namespace Tests\Feature\Auth;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertOk();
    }

    public function test_registration_screen_includes_team_invitation_context()
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['name' => 'Laravel Team']);
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $response = $this->get(route('register', ['invitation' => $invitation->code]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('auth/register')
            ->where('teamInvitation.code', $invitation->code)
            ->where('teamInvitation.teamName', 'Laravel Team'),
        );
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'user_type' => 'student',
            'affiliation' => 'College of Science',
            'id_number' => 'CMU-12345',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();

        $user = User::where('email', 'test@example.com')->first();
        $response->assertRedirect(route('dashboard'));
        $this->assertSame('student', $user->user_type);
        $this->assertSame('College of Science', $user->affiliation);
        $this->assertSame('CMU-12345', $user->id_number);
        $this->assertFalse($user->is_admin);
        $this->assertSame('pending', $user->verification_status);
    }

    public function test_student_and_faculty_registration_require_an_id_number(): void
    {
        $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Student User',
            'email' => 'student@example.com',
            'user_type' => 'student',
            'affiliation' => 'College of Science',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('id_number');

        $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Faculty User',
            'email' => 'faculty@example.com',
            'user_type' => 'faculty',
            'affiliation' => 'Library',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('id_number');
    }

    public function test_external_registration_does_not_require_an_id_number(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Outside User',
            'email' => 'outside@example.com',
            'user_type' => 'external',
            'affiliation' => 'Local Community',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'outside@example.com',
            'user_type' => 'external',
            'id_number' => null,
        ]);
    }

    public function test_registration_rejects_unknown_account_types_and_missing_affiliation(): void
    {
        $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Invalid User',
            'email' => 'invalid@example.com',
            'user_type' => 'admin',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors(['user_type', 'affiliation']);
    }
}
