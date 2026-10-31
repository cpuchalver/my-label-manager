<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertModelMissing;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();

    actingAs($this->admin);
});

it('forbids non-admin users from accessing the panel', function () {
    actingAs(User::factory()->create());

    get('/admin')->assertForbidden();
});

it('allows verified admins to access the panel', function () {
    get('/admin')->assertOk();
});

it('redirects unverified admins to the email verification prompt', function () {
    actingAs(User::factory()->admin()->unverified()->create());

    get('/admin')->assertRedirect(route('filament.admin.auth.email-verification.prompt'));
});

it('lists and searches users', function () {
    $users = User::factory()->count(3)->create();

    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($users->first()->email)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1));
});

it('creates an unverified user without sending a verification email', function () {
    Notification::fake();

    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'is_admin' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    $user = User::firstWhere('email', 'jane@example.com');

    expect($user->name)->toBe('Jane Doe')
        ->and($user->is_admin)->toBeTrue()
        ->and($user->email_verified_at)->toBeNull()
        ->and(Hash::check('secret-password', $user->password))->toBeTrue();

    Notification::assertNothingSent();
});

it('validates the user form', function (array $data, array $errors) {
    User::factory()->create(['email' => 'taken@example.com']);

    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            ...$data,
        ])
        ->call('create')
        ->assertHasFormErrors($errors)
        ->assertNotNotified();
})->with([
    'missing name' => [['name' => null], ['name' => 'required']],
    'invalid email' => [['email' => 'not-an-email'], ['email' => 'email']],
    'taken email' => [['email' => 'taken@example.com'], ['email' => 'unique']],
    'missing password' => [['password' => null, 'password_confirmation' => null], ['password' => 'required']],
    'mismatched password' => [['password_confirmation' => 'other-password'], ['password' => 'same']],
]);

it('keeps the password when it is left empty on edit', function () {
    $user = User::factory()->create();
    $originalPassword = $user->password;

    livewire(EditUser::class, ['record' => $user->id])
        ->fillForm(['name' => 'Updated name'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas(User::class, [
        'id' => $user->id,
        'name' => 'Updated name',
        'password' => $originalPassword,
    ]);
});

it('changes the password on edit', function () {
    $user = User::factory()->create();

    livewire(EditUser::class, ['record' => $user->id])
        ->fillForm([
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

it('resets email verification without sending an email when the email changes', function () {
    Notification::fake();

    $user = User::factory()->create();

    livewire(EditUser::class, ['record' => $user->id])
        ->fillForm(['email' => 'new-address@example.com'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->refresh())
        ->email->toBe('new-address@example.com')
        ->email_verified_at->toBeNull();

    Notification::assertNothingSent();
});

it('keeps email verification when the email does not change', function () {
    $user = User::factory()->create();

    livewire(EditUser::class, ['record' => $user->id])
        ->fillForm(['name' => 'Updated name'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

it('prevents admins from removing their own admin rights', function () {
    livewire(EditUser::class, ['record' => $this->admin->id])
        ->assertFormFieldDisabled('is_admin');
});

it('deletes another user', function () {
    $user = User::factory()->create();

    livewire(ListUsers::class)
        ->callAction(TestAction::make(DeleteAction::class)->table($user));

    assertModelMissing($user);
});

it('prevents admins from deleting themselves', function () {
    livewire(ListUsers::class)
        ->assertActionHidden(TestAction::make(DeleteAction::class)->table($this->admin));

    livewire(EditUser::class, ['record' => $this->admin->id])
        ->assertActionHidden(DeleteAction::class);
});
