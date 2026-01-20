<?php

namespace App\Enums;

enum PermissionEnum
{
    case VIEW_USERS;
    case UPDATE_USERS;
    case DELETE_USERS;

    case VIEW_ADMINS;
    case CREATE_ADMINS;
    case UPDATE_ADMINS;
    case DELETE_ADMINS;

    case MANAGE_PERMISSIONS;
    case MANAGE_ROLES;

    public function name(): string
    {
        return match ($this) {
            self::VIEW_USERS => 'VIEW_USERS',
            self::UPDATE_USERS => 'UPDATE_USERS',
            self::DELETE_USERS => 'DELETE_USERS',
            self::VIEW_ADMINS => 'VIEW_ADMINS',
            self::CREATE_ADMINS => 'CREATE_ADMINS',
            self::UPDATE_ADMINS => 'UPDATE_ADMINS',
            self::DELETE_ADMINS => 'DELETE_ADMINS',
            self::MANAGE_PERMISSIONS => 'MANAGE_PERMISSIONS',
            self::MANAGE_ROLES => 'MANAGE_ROLES',
        };
    }
}
