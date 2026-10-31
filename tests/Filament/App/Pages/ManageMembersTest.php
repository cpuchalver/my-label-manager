<?php

use App\Enums\LabelRole;
use App\Filament\App\Pages\ManageMembers;
use App\Models\Label;
use App\Models\User;
use Filament\Actions\Testing\TestAction;

use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->label = Label::factory()->create();
});

it('opens the members page for owners and administrators', function (LabelRole $role) {
    actingAsLabelMember($this->label, $role);

    get(ManageMembers::getUrl(panel: 'app', tenant: $this->label))->assertOk();
})->with([LabelRole::Owner, LabelRole::Admin]);

it('forbids the members page to managers and viewers', function (LabelRole $role) {
    actingAsLabelMember($this->label, $role);

    get(ManageMembers::getUrl(panel: 'app', tenant: $this->label))->assertForbidden();
})->with([LabelRole::Manager, LabelRole::Viewer]);

it('lists the members of the current label only', function () {
    $owner = actingAsLabelMember($this->label);
    $member = User::factory()->create();
    $this->label->addMember($member, LabelRole::Viewer);
    $outsider = User::factory()->create();
    Label::factory()->create()->addMember($outsider, LabelRole::Owner);
    withinLabel($this->label);

    livewire(ManageMembers::class)
        ->assertCanSeeTableRecords([$owner, $member])
        ->assertCanNotSeeTableRecords([$outsider]);
});

it('changes the role of a member', function () {
    actingAsLabelMember($this->label);
    $member = User::factory()->create();
    $this->label->addMember($member, LabelRole::Viewer);
    withinLabel($this->label);

    livewire(ManageMembers::class)
        ->callAction(TestAction::make('changeRole')->table($member), data: ['role' => LabelRole::Manager->value])
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($member->roleIn($this->label))->toBe(LabelRole::Manager);
});

it('removes a member', function () {
    actingAsLabelMember($this->label);
    $member = User::factory()->create();
    $this->label->addMember($member, LabelRole::Manager);
    withinLabel($this->label);

    livewire(ManageMembers::class)
        ->callAction(TestAction::make('removeMember')->table($member))
        ->assertNotified();

    expect($this->label->hasMember($member))->toBeFalse();
});

it('lets the owner transfer ownership to an administrator', function () {
    $owner = actingAsLabelMember($this->label);
    $administrator = User::factory()->create();
    $this->label->addMember($administrator, LabelRole::Admin);
    withinLabel($this->label);

    livewire(ManageMembers::class)
        ->callAction(TestAction::make('transferOwnership')->table($administrator))
        ->assertNotified();

    expect($administrator->roleIn($this->label))->toBe(LabelRole::Owner)
        ->and($owner->roleIn($this->label))->toBe(LabelRole::Admin);
});

it('only offers the transfer of ownership to the owner, towards administrators', function () {
    actingAsLabelMember($this->label, LabelRole::Admin);
    $otherAdministrator = User::factory()->create();
    $this->label->addMember($otherAdministrator, LabelRole::Admin);
    withinLabel($this->label);

    livewire(ManageMembers::class)
        ->assertActionHidden(TestAction::make('transferOwnership')->table($otherAdministrator));
});

it('protects the owner and the current user from changes', function () {
    $administrator = actingAsLabelMember($this->label, LabelRole::Admin);
    $owner = User::factory()->create();
    $this->label->addMember($owner, LabelRole::Owner);
    withinLabel($this->label);

    livewire(ManageMembers::class)
        ->assertActionHidden(TestAction::make('changeRole')->table($owner))
        ->assertActionHidden(TestAction::make('removeMember')->table($owner))
        ->assertActionHidden(TestAction::make('changeRole')->table($administrator))
        ->assertActionHidden(TestAction::make('removeMember')->table($administrator));
});
