<?php

namespace Tests\Feature\User\Auth;

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson(route('user.auth.register'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
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
                'code' => 201,
                'message' => 'Registration successful. Please verify your email.',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertTrue($user->hasRole(RoleEnum::USER->name()));
        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_user_registration_requires_first_name(): void
    {
        $response = $this->postJson(route('user.auth.register'), [
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name']);
    }

    public function test_user_registration_requires_last_name(): void
    {
        $response = $this->postJson(route('user.auth.register'), [
            'first_name' => 'John',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['last_name']);
    }

    public function test_user_registration_requires_email(): void
    {
        $response = $this->postJson(route('user.auth.register'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_registration_requires_unique_email(): void
    {
        User::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson(route('user.auth.register'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_registration_requires_password_confirmation(): void
    {
        $response = $this->postJson(route('user.auth.register'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_user_registration_hashes_password(): void
    {
        $response = $this->postJson(route('user.auth.register'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'john.doe@example.com')->first();
        $this->assertNotEquals('password123', $user->password);
        $this->assertTrue(Hash::check('password123', $user->password));
    }
}
