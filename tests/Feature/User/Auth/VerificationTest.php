<?php

namespace Tests\Feature\User\Auth;

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_can_verify_email(): void
    {
        Event::fake();

        $user = User::factory()->unverified()->create([
            'email' => 'user@example.com',
        ]);
        $user->setRole(RoleEnum::USER);

        $verificationUrl = URL::temporarySignedRoute(
            'user.auth.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'Email verified successfully',
            ]);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);
    }

    public function test_user_cannot_verify_email_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'user@example.com',
        ]);
        $user->setRole(RoleEnum::USER);

        $verificationUrl = URL::temporarySignedRoute(
            'user.auth.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => 'invalid-hash']
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(400)
            ->assertJson([
                'code' => 400,
                'message' => 'Invalid verification link.',
            ]);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_user_cannot_verify_already_verified_email(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);
        $user->setRole(RoleEnum::USER);

        $verificationUrl = URL::temporarySignedRoute(
            'user.auth.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(400)
            ->assertJson([
                'code' => 400,
                'message' => 'Email already verified.',
            ]);
    }

    public function test_user_can_resend_verification_email(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'user@example.com',
        ]);
        $user->setRole(RoleEnum::USER);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('user.auth.verification.resend'));

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'Verification email sent successfully',
            ]);
    }

    public function test_user_cannot_resend_verification_email_when_already_verified(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'email_verified_at' => now(),
        ]);
        $user->setRole(RoleEnum::USER);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson(route('user.auth.verification.resend'));

        $response->assertStatus(400)
            ->assertJson([
                'code' => 400,
                'message' => 'Email already verified.',
            ]);
    }

    public function test_verification_returns_404_for_nonexistent_user(): void
    {
        $verificationUrl = URL::temporarySignedRoute(
            'user.auth.verification.verify',
            now()->addMinutes(60),
            ['id' => '00000000-0000-0000-0000-000000000000', 'hash' => sha1('nonexistent@example.com')]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(404)
            ->assertJson([
                'code' => 404,
                'message' => 'User not found.',
            ]);
    }
}
