<?php

use App\Broadcasting\LabelChannel;
use App\Enums\LabelRole;
use App\Models\Label;
use App\Models\User;

it('lets the members of a label listen to its updates', function () {
    $label = Label::factory()->create();
    $member = User::factory()->create();
    $label->addMember($member, LabelRole::Viewer);

    expect((new LabelChannel)->join($member, $label))->toBeTrue();
});

it('refuses users who are not members of the label', function () {
    $label = Label::factory()->create();
    $otherLabel = Label::factory()->create();
    $otherMember = User::factory()->create();
    $otherLabel->addMember($otherMember, LabelRole::Owner);

    expect((new LabelChannel)->join($otherMember, $label))->toBeFalse();
});

it('refuses everyone on a suspended label', function () {
    $label = Label::factory()->suspended()->create();
    $member = User::factory()->create();
    $label->addMember($member, LabelRole::Owner);

    expect((new LabelChannel)->join($member, $label))->toBeFalse();
});
