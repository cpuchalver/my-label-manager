<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('allows verified users to access the app panel', function () {
    actingAs(User::factory()->create());

    get('/')->assertOk();
});

it('allows admins to access the app panel', function () {
    actingAs(User::factory()->admin()->create());

    get('/')->assertOk();
});

it('redirects unverified users to the email verification prompt', function () {
    actingAs(User::factory()->unverified()->create());

    get('/')->assertRedirect(route('filament.app.auth.email-verification.prompt'));
});

it('redirects guests to the app panel login', function () {
    get('/')->assertRedirect(route('filament.app.auth.login'));
});
