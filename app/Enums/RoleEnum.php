<?php

namespace App\Enums;

enum RoleEnum
{
    case OWNER;
    case ADMIN;
    case USER;

    public function name(): string
    {
        return match ($this) {
            self::OWNER => 'OWNER',
            self::ADMIN => 'ADMIN',
            self::USER => 'USER',
        };
    }
}
