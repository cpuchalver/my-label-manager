<?php

use App\Enums\LabelPermission;
use App\Enums\LabelRole;
use App\Models\Label;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\seed;

it('creates the roles and permissions even when model events are disabled', function () {
    Role::query()->delete();
    Permission::query()->delete();

    Model::withoutEvents(fn () => seed(RolesAndPermissionsSeeder::class));

    expect(Permission::query()->count())->toBe(count(LabelPermission::cases()));

    foreach (LabelRole::cases() as $labelRole) {
        expect(Role::findByName($labelRole->value)->permissions)->toHaveCount(count($labelRole->permissions()));
    }
});

it('can run again without duplicating roles or permissions', function () {
    seed(RolesAndPermissionsSeeder::class);

    expect(Role::query()->count())->toBe(count(LabelRole::cases()))
        ->and(Permission::query()->count())->toBe(count(LabelPermission::cases()));
});

it('seeds two demo labels with different roles from one label to the other', function () {
    seed(DatabaseSeeder::class);

    $demoLabel = Label::firstWhere('slug', 'demo-label');
    $secondLabel = Label::firstWhere('slug', 'second-label');
    $managerUser = User::firstWhere('email', 'manager@example.com');
    $testUser = User::firstWhere('email', 'test@example.com');

    expect($managerUser?->roleIn($demoLabel))->toBe(LabelRole::Manager)
        ->and($managerUser?->roleIn($secondLabel))->toBe(LabelRole::Admin)
        ->and($testUser?->roleIn($demoLabel))->toBe(LabelRole::Owner)
        ->and($testUser?->roleIn($secondLabel))->toBe(LabelRole::Viewer);
});
