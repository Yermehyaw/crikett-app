<?php

namespace App\Repositories\User\User;

use App\Models\User;
use LaravelEasyRepository\Repository;

interface UserRepository extends Repository
{
    public function findByEmail(string $email): ?User;

    public function findById(string|int $id): ?User;

    public function createUser(array $data): User;
}
