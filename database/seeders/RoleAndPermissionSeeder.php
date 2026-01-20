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
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [];
        foreach (PermissionEnum::cases() as $permission) {
            $permissions[$permission->name()] = Permission::firstOrCreate(
                ['name' => $permission->name(), 'guard_name' => 'web']
            );
        }

        // Create roles
        $ownerRole = Role::firstOrCreate(['name' => RoleEnum::OWNER->name(), 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => RoleEnum::ADMIN->name(), 'guard_name' => 'web']);
        $userRole = Role::firstOrCreate(['name' => RoleEnum::USER->name(), 'guard_name' => 'web']);

        // Assign all permissions to OWNER
        $ownerRole->syncPermissions($permissions);

        // Assign admin-related permissions to ADMIN (excluding MANAGE_PERMISSIONS and MANAGE_ROLES)
        $adminPermissions = array_filter($permissions, function ($key) {
            return ! in_array($key, [PermissionEnum::MANAGE_PERMISSIONS->name(), PermissionEnum::MANAGE_ROLES->name()]);
        }, ARRAY_FILTER_USE_KEY);

        $adminRole->syncPermissions($adminPermissions);

        // USER role gets no permissions by default
        $userRole->syncPermissions([]);
    }
}
