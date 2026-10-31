<?php

use App\Enums\LabelRole;
use App\Filament\App\Widgets\LabelOverview;
use App\Models\Invitation;
use App\Models\Label;
use App\Models\User;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->label = Label::factory()->create();

    actingAsLabelMember($this->label, LabelRole::Viewer);
    withinLabel($this->label);
});

it('shows the members and pending invitations of the label', function () {
    $this->label->addMember(User::factory()->create(), LabelRole::Manager);
    Invitation::factory()->for($this->label)->count(3)->create();
    Invitation::factory()->count(5)->create();

    livewire(LabelOverview::class)
        ->assertSeeInOrder(['Members', '2', 'Pending invitations', '3']);
});

it('refreshes when the label broadcasts an update', function () {
    $widget = livewire(LabelOverview::class)
        ->assertSeeInOrder(['Members', '1']);

    $this->label->addMember(User::factory()->create(), LabelRole::Manager);

    $widget
        ->dispatch("echo-private:labels.{$this->label->id},.label.activity-updated")
        ->assertSeeInOrder(['Members', '2']);
});
