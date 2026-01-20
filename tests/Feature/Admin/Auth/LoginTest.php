<?php

namespace Tests\Feature\Admin\Auth;

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->setRole(RoleEnum::ADMIN);

        $response = $this->postJson(route('admin.auth.login'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'first_name',
                        'last_name',
                        'date_of_birth',
                        'email',
                        'phone',
                        'address',
                        'city',
                        'state',
                        'email_verified_at',
                        'avatar',
                        'created_at',
                        'role' => [
                            'name',
                        ],
                        'permissions',
                    ],
                    'token',
                ],
            ])
            ->assertJson([
                'code' => 200,
                'message' => 'Login successful',
            ]);
    }

    public function test_admin_cannot_login_with_invalid_credentials(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $admin->setRole(RoleEnum::ADMIN);

        $response = $this->postJson(route('admin.auth.login'), [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'code' => 401,
                'message' => 'The provided credentials are incorrect.',
            ]);
    }

    public function test_user_cannot_login_to_admin_panel(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
        ]);
        $user->setRole(RoleEnum::USER);

        $response = $this->postJson(route('admin.auth.login'), [
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'code' => 401,
                'message' => 'The provided credentials are incorrect.',
            ]);
    }

    public function test_admin_login_requires_email(): void
    {
        $response = $this->postJson(route('admin.auth.login'), [
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_admin_login_requires_password(): void
    {
        $response = $this->postJson(route('admin.auth.login'), [
            'email' => 'admin@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
