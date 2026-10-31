<?php

use App\Enums\LabelPermission;
use App\Enums\LabelRole;
use App\Models\Label;
use App\Models\User;

/**
 * The permission matrix of the MVP specifications, section 3.
 *
 * @return array<string, array{LabelRole, array<int, LabelPermission>}>
 */
dataset('permission matrix', fn (): array => [
    'viewer' => [LabelRole::Viewer, [
        LabelPermission::ViewCatalog,
    ]],
    'manager' => [LabelRole::Manager, [
        LabelPermission::ViewCatalog,
        LabelPermission::ManageCatalog,
        LabelPermission::ImportCatalog,
    ]],
    'admin' => [LabelRole::Admin, [
        LabelPermission::ViewCatalog,
        LabelPermission::ManageCatalog,
        LabelPermission::ImportCatalog,
        LabelPermission::DeleteCatalog,
        LabelPermission::PublishContracts,
        LabelPermission::InvitePartners,
        LabelPermission::ManageMembers,
        LabelPermission::ManageSettings,
        LabelPermission::ViewAuditLog,
    ]],
    'owner' => [LabelRole::Owner, LabelPermission::cases()],
]);

it('grants each role exactly the permissions of the specifications', function (LabelRole $role, array $grantedPermissions) {
    $label = Label::factory()->create();
    $member = User::factory()->create();
    $label->addMember($member, $role);

    foreach (LabelPermission::cases() as $permission) {
        $isGranted = $label->withinPermissionsTeam(fn (): bool => $member->fresh()?->can($permission->value) ?? false);

        expect($isGranted)->toBe(in_array($permission, $grantedPermissions, true), "{$role->value} / {$permission->value}");
    }
})->with('permission matrix');

it('keeps the roles of a user separate from one label to another', function () {
    $ownedLabel = Label::factory()->create();
    $viewedLabel = Label::factory()->create();
    $user = User::factory()->create();
    $ownedLabel->addMember($user, LabelRole::Owner);
    $viewedLabel->addMember($user, LabelRole::Viewer);

    expect($user->roleIn($ownedLabel))->toBe(LabelRole::Owner)
        ->and($user->roleIn($viewedLabel))->toBe(LabelRole::Viewer)
        ->and($ownedLabel->withinPermissionsTeam(fn (): bool => $user->fresh()?->can(LabelPermission::ManageMembers->value) ?? false))->toBeTrue()
        ->and($viewedLabel->withinPermissionsTeam(fn (): bool => $user->fresh()?->can(LabelPermission::ManageMembers->value) ?? false))->toBeFalse();
});

it('changes the role of a member', function () {
    $label = Label::factory()->create();
    $member = User::factory()->create();
    $label->addMember($member, LabelRole::Viewer);

    $label->changeMemberRole($member, LabelRole::Manager);

    expect($member->roleIn($label))->toBe(LabelRole::Manager);
});

it('refuses to give the owner role without a transfer', function () {
    $label = Label::factory()->create();
    $member = User::factory()->create();
    $label->addMember($member, LabelRole::Admin);

    $label->changeMemberRole($member, LabelRole::Owner);
})->throws(LogicException::class);

it('refuses to change the role of the owner', function () {
    $label = Label::factory()->create();
    $owner = User::factory()->create();
    $label->addMember($owner, LabelRole::Owner);

    $label->changeMemberRole($owner, LabelRole::Viewer);
})->throws(LogicException::class);

it('removes a member and their role from the label', function () {
    $label = Label::factory()->create();
    $member = User::factory()->create();
    $label->addMember($member, LabelRole::Manager);

    $label->removeMember($member);

    expect($label->hasMember($member))->toBeFalse()
        ->and($member->roleIn($label))->toBeNull();
});

it('refuses to remove the owner', function () {
    $label = Label::factory()->create();
    $owner = User::factory()->create();
    $label->addMember($owner, LabelRole::Owner);

    $label->removeMember($owner);
})->throws(LogicException::class);

it('transfers ownership to an administrator and keeps a single owner', function () {
    $label = Label::factory()->create();
    $owner = User::factory()->create();
    $administrator = User::factory()->create();
    $label->addMember($owner, LabelRole::Owner);
    $label->addMember($administrator, LabelRole::Admin);

    $label->transferOwnership($administrator);

    expect($administrator->roleIn($label))->toBe(LabelRole::Owner)
        ->and($owner->roleIn($label))->toBe(LabelRole::Admin)
        ->and($label->owner()?->is($administrator))->toBeTrue();
});

it('refuses to transfer ownership to someone outside the label', function () {
    $label = Label::factory()->create();
    $label->addMember(User::factory()->create(), LabelRole::Owner);

    $label->transferOwnership(User::factory()->create());
})->throws(LogicException::class);
