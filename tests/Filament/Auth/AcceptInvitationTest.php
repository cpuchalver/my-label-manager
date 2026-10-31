<?php

use App\Enums\LabelRole;
use App\Filament\Auth\AcceptInvitation;
use App\Models\Invitation;
use App\Models\Label;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Filament::setCurrentPanel('app');

    $this->label = Label::factory()->create();
    $this->invitation = Invitation::factory()
        ->for($this->label)
        ->withToken('valid-token')
        ->create(['email' => 'jane@example.com', 'role' => LabelRole::Manager]);
});

it('creates the account of a new person and adds them to the label', function () {
    livewire(AcceptInvitation::class, ['token' => 'valid-token'])
        ->fillForm([
            'name' => 'Jane Doe',
            'password' => 'a-long-password',
            'passwordConfirmation' => 'a-long-password',
        ])
        ->call('register')
        ->assertHasNoFormErrors()
        ->assertRedirect(Dashboard::getUrl(panel: 'app', tenant: $this->label));

    $user = User::firstWhere('email', 'jane@example.com');

    expect($user?->hasVerifiedEmail())->toBeTrue()
        ->and($user?->roleIn($this->label))->toBe(LabelRole::Manager)
        ->and($this->invitation->fresh()?->isPending())->toBeFalse();

    assertAuthenticatedAs($user);
});

it('asks a person who already has an account to log in, then brings them back', function () {
    User::factory()->create(['email' => 'jane@example.com']);

    get(route('filament.app.invitations.accept', ['token' => 'valid-token']))
        ->assertOk()
        ->assertSee('Log in to accept')
        ->assertSessionHas('url.intended', route('filament.app.invitations.accept', ['token' => 'valid-token']));
});

it('accepts the invitation as soon as the invited user opens the link while logged in', function () {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    actingAs($user);

    get(route('filament.app.invitations.accept', ['token' => 'valid-token']))
        ->assertRedirect(Dashboard::getUrl(panel: 'app', tenant: $this->label));

    expect($user->roleIn($this->label))->toBe(LabelRole::Manager);
});

it('does not let another logged in user accept the invitation', function () {
    actingAs(User::factory()->create(['email' => 'john@example.com']));

    get(route('filament.app.invitations.accept', ['token' => 'valid-token']))
        ->assertOk()
        ->assertSee('This invitation was sent to jane@example.com');

    expect($this->invitation->fresh()?->isPending())->toBeTrue();
});

it('refuses an unknown, expired or used link', function (string $token) {
    Invitation::factory()->for($this->label)->expired()->withToken('expired-token')->create();
    Invitation::factory()->for($this->label)->accepted()->withToken('used-token')->create();

    get(route('filament.app.invitations.accept', ['token' => $token]))
        ->assertOk()
        ->assertSee('This invitation is invalid');
})->with(['unknown-token', 'expired-token', 'used-token']);

it('does not create an account when the invited email already has one', function () {
    User::factory()->create(['email' => 'jane@example.com']);

    livewire(AcceptInvitation::class, ['token' => 'valid-token'])
        ->fillForm([
            'name' => 'Jane Doe',
            'password' => 'a-long-password',
            'passwordConfirmation' => 'a-long-password',
        ])
        ->call('register')
        ->assertForbidden();
});
