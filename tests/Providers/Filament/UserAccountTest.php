<?php

use App\Models\Label;
use App\Models\User;
use Filament\Auth\Notifications\NoticeOfEmailChangeRequest;
use Filament\Auth\Notifications\ResetPassword;
use Filament\Auth\Notifications\VerifyEmailChange;
use Filament\Auth\Pages\EditProfile;
use Filament\Auth\Pages\Login;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

dataset('panels', ['app', 'admin']);

it('sends a password reset link', function (string $panel) {
    Notification::fake();
    Filament::setCurrentPanel($panel);
    $user = User::factory()->admin()->hasAttached(Label::factory())->create();

    get(Filament::getRequestPasswordResetUrl())->assertOk();

    livewire(RequestPasswordReset::class)
        ->fillForm(['email' => $user->email])
        ->call('request')
        ->assertNotified();

    Notification::assertSentTo($user, ResetPassword::class);
})->with('panels');

it('sends no password reset link to users who cannot access the panel', function () {
    Notification::fake();
    Filament::setCurrentPanel('admin');
    $user = User::factory()->create();

    livewire(RequestPasswordReset::class)
        ->fillForm(['email' => $user->email])
        ->call('request');

    Notification::assertNothingSentTo($user);
});

it('opens the profile page of a label member', function () {
    actingAsLabelMember();
    Filament::setCurrentPanel('app');

    get(Filament::getProfileUrl())->assertOk();
});

it('updates the name from the profile page', function () {
    $user = actingAsLabelMember();
    Filament::setCurrentPanel('app');

    livewire(EditProfile::class)
        ->fillForm(['name' => 'New Name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->refresh()->name)->toBe('New Name');
});

it('asks to verify a new email address before changing it', function () {
    Notification::fake();
    $user = actingAsLabelMember();
    $previousEmail = $user->email;
    Filament::setCurrentPanel('app');

    livewire(EditProfile::class)
        ->fillForm([
            'email' => 'new-address@example.com',
            'currentPassword' => 'password',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->refresh()->email)->toBe($previousEmail);

    Notification::assertSentTo($user, NoticeOfEmailChangeRequest::class);
    Notification::assertSentOnDemand(VerifyEmailChange::class);
});

it('challenges users who set up app authentication when they log in', function (string $panel) {
    Filament::setCurrentPanel($panel);
    $user = User::factory()->admin()->hasAttached(Label::factory())->withAppAuthentication()->create();

    livewire(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertNotSet('userUndertakingMultiFactorAuthentication', null)
        ->assertNoRedirect();

    assertGuest();
})->with('panels');

it('does not store the app authentication secret in clear text', function () {
    $user = User::factory()->withAppAuthentication()->create();

    expect($user->getRawOriginal('app_authentication_secret'))->not->toBe($user->getAppAuthenticationSecret())
        ->and($user->toArray())->not->toHaveKeys(['app_authentication_secret', 'app_authentication_recovery_codes']);
});
