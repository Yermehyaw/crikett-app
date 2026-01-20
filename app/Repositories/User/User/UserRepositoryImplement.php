<?php

namespace App\Repositories\User\User;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use LaravelEasyRepository\Implementations\Eloquent;

class UserRepositoryImplement extends Eloquent implements UserRepository
{
    protected User $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * Find user by email address.
     *
     * Retrieves a user with the specified email address.
     * Returns null if user is not found.
     *
     * @param  string  $email  The user's email address
     * @return User|null Returns the User model instance or null if not found
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Find user by ID.
     *
     * Retrieves a user with the specified ID.
     * Returns null if user is not found.
     *
     * @param  string|int  $id  The user's ID
     * @return User|null Returns the User model instance or null if not found
     */
    public function findById(string|int $id): ?User
    {
        return $this->model->find($id);
    }

    /**
     * Create a new user.
     *
     * Creates a new user with the provided data, hashes the password,
     * and assigns the USER role.
     *
     * @param  array  $data  The user data including name, email, and password
     * @return User Returns the newly created User model instance
     */
    public function createUser(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        $user = $this->model->create($data);
        $user->setRole(RoleEnum::USER);

        return $user;
    }
}
