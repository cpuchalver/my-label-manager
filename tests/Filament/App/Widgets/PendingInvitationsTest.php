<?php

use App\Filament\App\Widgets\PendingInvitations;
use App\Models\Invitation;
use App\Models\Label;
use App\Notifications\LabelInvitation;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Notification;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->label = Label::factory()->create();

    actingAsLabelMember($this->label);
    withinLabel($this->label);
});

it('lists the pending invitations of the current label only', function () {
    $pendingInvitation = Invitation::factory()->for($this->label)->create();
    $expiredInvitation = Invitation::factory()->for($this->label)->expired()->create();
    $otherLabelInvitation = Invitation::factory()->create();

    livewire(PendingInvitations::class)
        ->assertCanSeeTableRecords([$pendingInvitation])
        ->assertCanNotSeeTableRecords([$expiredInvitation, $otherLabelInvitation]);
});

it('sends an invitation again', function () {
    Notification::fake();
    $invitation = Invitation::factory()->for($this->label)->withToken('previous-token')->create();

    livewire(PendingInvitations::class)
        ->callAction(TestAction::make('resendInvitation')->table($invitation))
        ->assertNotified();

    expect(Invitation::findPendingByToken('previous-token'))->toBeNull();

    Notification::assertSentOnDemand(LabelInvitation::class);
});

it('cancels an invitation', function () {
    $invitation = Invitation::factory()->for($this->label)->create();

    livewire(PendingInvitations::class)
        ->callAction(TestAction::make('cancelInvitation')->table($invitation))
        ->assertNotified();

    expect(Invitation::find($invitation->id))->toBeNull();
});
