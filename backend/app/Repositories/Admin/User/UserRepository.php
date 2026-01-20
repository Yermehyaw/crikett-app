<?php

namespace App\Repositories\Admin\User;

use App\Models\User;
use LaravelEasyRepository\Repository;

interface UserRepository extends Repository
{
    public function findByEmail(string $email): ?User;

    public function findById(string|int $id): ?User;
}
