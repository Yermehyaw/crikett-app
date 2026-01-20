<?php

namespace Tests\Feature\Admin\Auth;

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveAdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_inactive_admin_is_logged_out_and_blocked_on_authenticated_routes(): void
    {
        $admin = User::factory()->create([
            'is_active' => false,
            'email_verified_at' => now(),
        ]);
        $admin->setRole(RoleEnum::ADMIN);

        $plainTextToken = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$plainTextToken}")
            ->getJson(route('admin.profile.show'));

        $response->assertStatus(403)
            ->assertJson([
                'code' => 403,
                'message' => 'Your account is suspended. Please contact support.',
                'success' => false,
            ]);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => hash('sha256', explode('|', $plainTextToken)[1]),
        ]);
    }
}
