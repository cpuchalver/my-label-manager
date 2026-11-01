<?php

use App\Enums\LabelRole;
use App\Models\Invitation;
use App\Models\Label;
use App\Models\User;
use App\Notifications\LabelInvitation;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->label = Label::factory()->create();
});

it('sends an invitation link to the invited email address', function () {
    $invitation = $this->label->inviteMember('Jane@Example.com', LabelRole::Manager);

    expect($invitation->email)->toBe('jane@example.com')
        ->and($invitation->role)->toBe(LabelRole::Manager)
        ->and($invitation->isPending())->toBeTrue();

    Notification::assertSentOnDemand(
        LabelInvitation::class,
        function (LabelInvitation $notification, array $channels, AnonymousNotifiable $notifiable) use ($invitation): bool {
            return $notifiable->routes['mail'] === 'jane@example.com'
                && Invitation::findPendingByToken($notification->token)?->is($invitation) === true;
        },
    );
});

it('stores only a hash of the link token', function () {
    $invitation = $this->label->inviteMember('jane@example.com', LabelRole::Viewer);

    Notification::assertSentOnDemand(LabelInvitation::class, function (LabelInvitation $notification) use ($invitation): bool {
        return $invitation->fresh()?->getRawOriginal('token_hash') === Invitation::hashToken($notification->token)
            && $invitation->fresh()?->getRawOriginal('token_hash') !== $notification->token;
    });
});

it('replaces a pending invitation to the same address', function () {
    $firstInvitation = $this->label->inviteMember('jane@example.com', LabelRole::Viewer);
    $secondInvitation = $this->label->inviteMember('jane@example.com', LabelRole::Admin);

    expect(Invitation::find($firstInvitation->id))->toBeNull()
        ->and($this->label->pendingInvitations()->sole()->is($secondInvitation))->toBeTrue();
});

it('makes the previous link unusable when an invitation is sent again', function () {
    $invitation = Invitation::factory()->for($this->label)->withToken('previous-token')->create();

    $invitation->send();

    expect(Invitation::findPendingByToken('previous-token'))->toBeNull();
});

it('refuses to invite someone as owner', function () {
    $this->label->inviteMember('jane@example.com', LabelRole::Owner);
})->throws(LogicException::class);

it('refuses to invite a member of the label, whatever the case of the address', function () {
    $this->label->addMember(User::factory()->create(['email' => 'jane@example.com']), LabelRole::Viewer);

    $this->label->inviteMember('JANE@example.com', LabelRole::Viewer);
})->throws(LogicException::class);

it('ignores expired and accepted invitations', function () {
    Invitation::factory()->for($this->label)->expired()->withToken('expired-token')->create();
    Invitation::factory()->for($this->label)->accepted()->withToken('accepted-token')->create();

    expect(Invitation::findPendingByToken('expired-token'))->toBeNull()
        ->and(Invitation::findPendingByToken('accepted-token'))->toBeNull();
});

it('adds the invited user to the label with the invited role', function () {
    $user = User::factory()->create(['email' => 'jane@example.com']);
    $invitation = Invitation::factory()->for($this->label)->create(['email' => 'jane@example.com', 'role' => LabelRole::Manager]);

    $invitation->accept($user);

    expect($this->label->hasMember($user))->toBeTrue()
        ->and($user->roleIn($this->label))->toBe(LabelRole::Manager)
        ->and($invitation->fresh()?->isPending())->toBeFalse();
});

it('refuses an invitation accepted by another email address', function () {
    $invitation = Invitation::factory()->for($this->label)->create(['email' => 'jane@example.com']);

    $invitation->accept(User::factory()->create(['email' => 'john@example.com']));
})->throws(LogicException::class);

it('refuses to accept an invitation twice', function () {
    $user = User::factory()->create(['email' => 'jane@example.com']);
    $invitation = Invitation::factory()->for($this->label)->create(['email' => 'jane@example.com']);
    $invitation->accept($user);

    $invitation->accept($user);
})->throws(LogicException::class);
