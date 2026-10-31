<?php

use App\Models\Label;
use App\Models\User;
use Filament\Pages\Dashboard;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects verified members to their label dashboard', function () {
    $label = Label::factory()->create();

    actingAs(User::factory()->hasAttached($label)->create());

    get('/')->assertRedirect(Dashboard::getUrl(panel: 'app', tenant: $label));
});

it('allows verified members to access their label dashboard', function () {
    $label = Label::factory()->create();

    actingAs(User::factory()->hasAttached($label)->create());

    get(Dashboard::getUrl(panel: 'app', tenant: $label))->assertOk();
});

it('allows admins who are members of a label to access the app panel', function () {
    $label = Label::factory()->create();

    actingAs(User::factory()->admin()->hasAttached($label)->create());

    get(Dashboard::getUrl(panel: 'app', tenant: $label))->assertOk();
});

it('forbids users who are not members of any label', function () {
    actingAs(User::factory()->create());

    get('/')->assertForbidden();
});

it('redirects unverified users to the email verification prompt', function () {
    $label = Label::factory()->create();

    actingAs(User::factory()->unverified()->hasAttached($label)->create());

    get(Dashboard::getUrl(panel: 'app', tenant: $label))
        ->assertRedirect(route('filament.app.auth.email-verification.prompt'));
});

it('redirects guests to the app panel login', function () {
    get('/')->assertRedirect(route('filament.app.auth.login'));
});
