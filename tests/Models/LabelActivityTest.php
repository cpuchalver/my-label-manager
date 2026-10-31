<?php

use App\Enums\LabelRole;
use App\Events\LabelActivityUpdated;
use App\Models\Invitation;
use App\Models\Label;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->label = Label::factory()->create();
    $this->owner = User::factory()->create();
    $this->label->addMember($this->owner, LabelRole::Owner);
});

dataset('label changes', [
    'member added' => fn () => fn (Label $label) => $label->addMember(User::factory()->create(), LabelRole::Viewer),
    'role changed' => fn () => function (Label $label) {
        $member = User::factory()->create();
        $label->addMember($member, LabelRole::Viewer);
        Event::fake([LabelActivityUpdated::class]);
        $label->changeMemberRole($member, LabelRole::Manager);
    },
    'member removed' => fn () => function (Label $label) {
        $member = User::factory()->create();
        $label->addMember($member, LabelRole::Viewer);
        Event::fake([LabelActivityUpdated::class]);
        $label->removeMember($member);
    },
    'ownership transferred' => fn () => function (Label $label) {
        $administrator = User::factory()->create();
        $label->addMember($administrator, LabelRole::Admin);
        Event::fake([LabelActivityUpdated::class]);
        $label->transferOwnership($administrator);
    },
    'invitation sent' => fn () => fn (Label $label) => $label->inviteMember('jane@example.com', LabelRole::Viewer),
    'invitation accepted' => fn () => function (Label $label) {
        $invitation = Invitation::factory()->for($label)->create(['email' => 'jane@example.com']);
        $user = User::factory()->create(['email' => 'jane@example.com']);
        Event::fake([LabelActivityUpdated::class]);
        $invitation->accept($user);
    },
    'invitation cancelled' => fn () => function (Label $label) {
        $invitation = Invitation::factory()->for($label)->create();
        Event::fake([LabelActivityUpdated::class]);
        $invitation->cancel();
    },
]);

it('broadcasts an update to the label when its data changes', function (Closure $change) {
    Event::fake([LabelActivityUpdated::class]);

    $change($this->label);

    Event::assertDispatched(LabelActivityUpdated::class, fn (LabelActivityUpdated $event): bool => $event->label->is($this->label));
})->with('label changes');

it('broadcasts on the private channel of the label, without data', function () {
    $event = new LabelActivityUpdated($this->label);

    expect($event->broadcastOn()[0]->name)->toBe("private-labels.{$this->label->id}")
        ->and($event->broadcastAs())->toBe('label.activity-updated')
        ->and($event->broadcastWith())->toBe(['labelId' => $this->label->id]);
});

it('notifies the owner and administrators when someone joins through an invitation', function () {
    $administrator = User::factory()->create();
    $manager = User::factory()->create();
    $this->label->addMember($administrator, LabelRole::Admin);
    $this->label->addMember($manager, LabelRole::Manager);
    $newMember = User::factory()->create(['email' => 'jane@example.com']);
    $invitation = Invitation::factory()->for($this->label)->create(['email' => 'jane@example.com']);

    $invitation->accept($newMember);

    expect($this->owner->notifications()->count())->toBe(1)
        ->and($administrator->notifications()->count())->toBe(1)
        ->and($manager->notifications()->count())->toBe(0)
        ->and($newMember->notifications()->count())->toBe(0);
});
