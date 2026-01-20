<?php

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [];
        foreach (PermissionEnum::cases() as $permission) {
            $permissions[$permission->name()] = Permission::firstOrCreate(
                ['name' => $permission->name(), 'guard_name' => 'web']
            );
        }

        $ownerRole = Role::firstOrCreate(['name' => RoleEnum::OWNER->name(), 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => RoleEnum::ADMIN->name(), 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => RoleEnum::USER->name(), 'guard_name' => 'web']);

        $ownerRole->syncPermissions($permissions);

        $adminPermissions = [
            PermissionEnum::VIEW_USERS->name(),
            PermissionEnum::UPDATE_USERS->name(),
            PermissionEnum::DELETE_USERS->name(),
            PermissionEnum::VIEW_ADMINS->name(),
        ];
        $adminRole->syncPermissions($adminPermissions);

        $userRole->syncPermissions([]);
    }
}
