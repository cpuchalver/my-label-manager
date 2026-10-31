<?php

use App\Models\Label;
use App\Models\User;
use Filament\Pages\Dashboard;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('returns a not found error for a label the user is not a member of', function () {
    actingAsLabelMember();

    $otherLabel = Label::factory()->create();

    get(Dashboard::getUrl(panel: 'app', tenant: $otherLabel))->assertNotFound();
});

it('returns a not found error for a label that does not exist', function () {
    actingAsLabelMember();

    get('/unknown-label')->assertNotFound();
});

it('forbids access to a suspended label with an explicit message', function () {
    $label = Label::factory()->suspended()->create();

    actingAsLabelMember($label);

    get(Dashboard::getUrl(panel: 'app', tenant: $label))
        ->assertForbidden()
        ->assertSee('This label is suspended.');
});

it('lists every label of the user in the tenant switcher', function () {
    $firstLabel = Label::factory()->create();
    $secondLabel = Label::factory()->create();
    $otherLabel = Label::factory()->create();

    actingAs(User::factory()->hasAttached($firstLabel)->hasAttached($secondLabel)->create());

    get(Dashboard::getUrl(panel: 'app', tenant: $firstLabel))
        ->assertOk()
        ->assertSee($secondLabel->name)
        ->assertDontSee($otherLabel->name);
});

it('remembers the last label the user visited', function () {
    $firstLabel = Label::factory()->create();
    $secondLabel = Label::factory()->create();
    $user = User::factory()->hasAttached($firstLabel)->hasAttached($secondLabel)->create();

    actingAs($user);

    get(Dashboard::getUrl(panel: 'app', tenant: $secondLabel))->assertOk();

    expect($user->fresh()?->latest_label_id)->toBe($secondLabel->id);
});

it('redirects the user to the last label they visited', function () {
    $firstLabel = Label::factory()->create();
    $secondLabel = Label::factory()->create();
    $user = User::factory()->hasAttached($firstLabel)->hasAttached($secondLabel)->create();
    $user->rememberLatestLabel($secondLabel);

    actingAs($user);

    get('/')->assertRedirect(Dashboard::getUrl(panel: 'app', tenant: $secondLabel));
});

it('ignores the last visited label once the user has left it', function () {
    $currentLabel = Label::factory()->create();
    $formerLabel = Label::factory()->create();
    $user = User::factory()->hasAttached($currentLabel)->hasAttached($formerLabel)->create();
    $user->rememberLatestLabel($formerLabel);
    $user->labels()->detach($formerLabel);

    actingAs($user);

    get('/')->assertRedirect(Dashboard::getUrl(panel: 'app', tenant: $currentLabel));
});
