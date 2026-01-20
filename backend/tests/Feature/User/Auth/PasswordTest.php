<?php

namespace Tests\Feature\User\Auth;

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_can_request_password_reset(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);
        $user->setRole(RoleEnum::USER);

        $response = $this->postJson(route('user.auth.password.forgot'), [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'Password reset link sent to your email.',
            ]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_user_cannot_request_password_reset_with_invalid_email(): void
    {
        $response = $this->postJson(route('user.auth.password.forgot'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'code' => 400,
                'message' => 'Unable to send password reset link. Please try again.',
            ]);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('old-password'),
        ]);
        $user->setRole(RoleEnum::USER);

        $token = Password::createToken($user);

        $response = $this->postJson(route('user.auth.password.reset'), [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'Password reset successfully.',
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('new-password123', $user->password));
        $this->assertFalse(Hash::check('old-password', $user->password));
    }

    public function test_user_cannot_reset_password_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('old-password'),
        ]);
        $user->setRole(RoleEnum::USER);

        $response = $this->postJson(route('user.auth.password.reset'), [
            'token' => 'invalid-token',
            'email' => 'user@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'code' => 400,
                'message' => 'Invalid or expired reset token.',
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('old-password', $user->password));
    }

    public function test_user_cannot_reset_password_with_mismatched_confirmation(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
        ]);
        $user->setRole(RoleEnum::USER);

        $token = Password::createToken($user);

        $response = $this->postJson(route('user.auth.password.reset'), [
            'token' => $token,
            'email' => 'user@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_forgot_password_requires_email(): void
    {
        $response = $this->postJson(route('user.auth.password.forgot'), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_reset_password_requires_token(): void
    {
        $response = $this->postJson(route('user.auth.password.reset'), [
            'email' => 'user@example.com',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }
}
