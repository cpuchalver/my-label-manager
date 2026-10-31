<?php

namespace Database\Seeders;

use App\Enums\LabelPermission;
use App\Enums\LabelRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Create the label roles and permissions, and keep them in sync with the enums. Safe to run on every deployment.
     *
     * The permission cache is cleared explicitly because model events, which normally clear it, may be disabled.
     */
    public function run(): void
    {
        $permissionRegistrar = app(PermissionRegistrar::class);

        $permissionRegistrar->forgetCachedPermissions();
        setPermissionsTeamId(null);

        foreach (LabelPermission::cases() as $permission) {
            Permission::findOrCreate($permission->value);
        }

        $permissionRegistrar->forgetCachedPermissions();

        foreach (LabelRole::cases() as $labelRole) {
            Role::findOrCreate($labelRole->value)->syncPermissions(
                array_map(fn (LabelPermission $permission): string => $permission->value, $labelRole->permissions()),
            );
        }

        $permissionRegistrar->forgetCachedPermissions();
    }
}
