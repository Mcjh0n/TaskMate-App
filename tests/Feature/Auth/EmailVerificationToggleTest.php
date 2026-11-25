<?php

namespace Tests\Feature\Auth;

use App\Enums\VerificationStatus;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerificationToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_can_be_disabled_without_disabling_taskmate_account_review(): void
    {
        config(['taskmate.email_verification_enabled' => false]);
        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'Synthetic Student',
            'email' => 'toggle-student@cmu.edu.ph',
            'password' => 'password',
            'password_confirmation' => 'password',
            'user_type' => 'student',
            'id_number' => '1234567890',
            'affiliation' => 'Synthetic College and Program',
        ])->assertRedirect();

        $user = User::query()->where('email', 'toggle-student@cmu.edu.ph')->firstOrFail();

        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertSame(VerificationStatus::Pending, $user->verification_status);
        Notification::assertNotSentTo($user, VerifyEmail::class);

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirect();

        Notification::assertNotSentTo($user, VerifyEmail::class);

        $this->actingAs($user)->get(route('taskmate.dashboard'))->assertOk();
    }
}
