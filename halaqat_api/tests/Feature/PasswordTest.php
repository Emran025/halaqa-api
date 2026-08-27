<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_returns_accepted_and_sends_reset_notification_for_existing_user(): void
    {
        Notification::fake();
        $user = User::factory()->student()->create(['email' => 'password@example.test', 'password' => 'password123']);

        $this->postJson('/api/v1/auth/password/forgot', [
            'email' => $user->email,
        ])->assertAccepted()->assertJson(['message' => 'If the account exists, password reset instructions have been sent.']);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_does_not_disclose_unknown_email(): void
    {
        Notification::fake();

        $this->postJson('/api/v1/auth/password/forgot', [
            'email' => 'unknown@example.test',
        ])->assertAccepted()->assertJsonStructure(['message'])->assertJsonMissingPath('data');

        Notification::assertNothingSent();
    }

    public function test_reset_password_accepts_valid_token_and_changes_password(): void
    {
        $user = User::factory()->student()->create(['email' => 'reset@example.test', 'password' => 'old-password']);
        $token = Password::createToken($user);

        $this->postJson('/api/v1/auth/password/reset', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk()->assertJson(['message' => 'Password reset successfully.']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'new-password',
        ])->assertOk();
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        $user = User::factory()->student()->create(['email' => 'invalid-token@example.test']);

        $this->postJson('/api/v1/auth/password/reset', [
            'email' => $user->email,
            'token' => 'invalid-token',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertUnprocessable()->assertJsonPath('field_errors.0.field', 'token');
    }

    public function test_change_password_requires_current_password_and_revokes_tokens(): void
    {
        $user = User::factory()->student()->create(['email' => 'change@example.test', 'password' => 'old-password']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/password/change', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'new-password',
        ])->assertOk();
    }

    public function test_change_password_rejects_wrong_current_password_and_unknown_fields(): void
    {
        $user = User::factory()->student()->create(['email' => 'wrong-current@example.test', 'password' => 'old-password']);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/password/change', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertUnauthorized();

        $this->withToken($token)->postJson('/api/v1/auth/password/change', [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
            'unexpected' => true,
        ])->assertUnprocessable()->assertJsonPath('field_errors.0.field', '_schema');
    }
}
