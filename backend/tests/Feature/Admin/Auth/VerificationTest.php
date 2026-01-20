<?php

namespace Tests\Feature\Admin\Auth;

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

    public function test_admin_can_verify_email(): void
    {
        Event::fake();

        $admin = User::factory()->unverified()->create([
            'email' => 'admin@example.com',
        ]);
        $admin->setRole(RoleEnum::ADMIN);

        $verificationUrl = URL::temporarySignedRoute(
            'admin.auth.verification.verify',
            now()->addMinutes(60),
            ['id' => $admin->id, 'hash' => sha1($admin->email)]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'Email verified successfully',
            ]);

        $this->assertTrue($admin->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);
    }

    public function test_admin_cannot_verify_email_with_invalid_hash(): void
    {
        $admin = User::factory()->unverified()->create([
            'email' => 'admin@example.com',
        ]);
        $admin->setRole(RoleEnum::ADMIN);

        $verificationUrl = URL::temporarySignedRoute(
            'admin.auth.verification.verify',
            now()->addMinutes(60),
            ['id' => $admin->id, 'hash' => 'invalid-hash']
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(400)
            ->assertJson([
                'code' => 400,
                'message' => 'Invalid verification link.',
            ]);

        $this->assertFalse($admin->fresh()->hasVerifiedEmail());
    }

    public function test_admin_cannot_verify_already_verified_email(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
        ]);
        $admin->setRole(RoleEnum::ADMIN);

        $verificationUrl = URL::temporarySignedRoute(
            'admin.auth.verification.verify',
            now()->addMinutes(60),
            ['id' => $admin->id, 'hash' => sha1($admin->email)]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(400)
            ->assertJson([
                'code' => 400,
                'message' => 'Email already verified.',
            ]);
    }

    public function test_admin_can_resend_verification_email(): void
    {
        $admin = User::factory()->unverified()->create([
            'email' => 'admin@example.com',
        ]);
        $admin->setRole(RoleEnum::ADMIN);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson(route('admin.auth.verification.resend'));

        $response->assertStatus(200)
            ->assertJson([
                'code' => 200,
                'message' => 'Verification email sent successfully',
            ]);
    }

    public function test_admin_cannot_resend_verification_email_when_already_verified(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'email_verified_at' => now(),
        ]);
        $admin->setRole(RoleEnum::ADMIN);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson(route('admin.auth.verification.resend'));

        $response->assertStatus(400)
            ->assertJson([
                'code' => 400,
                'message' => 'Email already verified.',
            ]);
    }
}
