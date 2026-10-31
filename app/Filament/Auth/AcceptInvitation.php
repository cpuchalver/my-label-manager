<?php

namespace App\Filament\Auth;

use App\Models\Invitation;
use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard;
use Filament\Pages\SimplePage;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Locked;

/**
 * @property-read Schema $form
 */
class AcceptInvitation extends SimplePage
{
    use WithRateLimiting;

    #[Locked]
    public string $token = '';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(string $token): void
    {
        $this->token = $token;

        $invitation = $this->invitation();
        $user = Filament::auth()->user();

        if ($invitation === null) {
            return;
        }

        if ($user instanceof User && $invitation->isFor($user)) {
            $this->acceptAndRedirect($invitation, $user);

            return;
        }

        if ($user === null && $this->invitedUserExists($invitation)) {
            session()->put('url.intended', route('filament.app.invitations.accept', ['token' => $token]));
        }

        $this->form->fill();
    }

    public function getTitle(): string|Htmlable
    {
        return __('Invitation');
    }

    public function getHeading(): string|Htmlable|null
    {
        $invitation = $this->invitation();

        if ($invitation === null) {
            return __('Invitation');
        }

        return __('Join :label', ['label' => $invitation->label->name]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->autofocus(),
                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule(Password::default())
                    ->same('passwordConfirmation'),
                TextInput::make('passwordConfirmation')
                    ->label('Confirm password')
                    ->password()
                    ->revealable()
                    ->required()
                    ->dehydrated(false),
            ])
            ->statePath('data');
    }

    public function content(Schema $schema): Schema
    {
        $invitation = $this->invitation();

        if ($invitation === null) {
            return $schema->components([
                Text::make(__('This invitation is invalid, has expired or has already been used. Ask the label to send you a new one.')),
            ]);
        }

        if (Filament::auth()->check()) {
            return $schema->components([
                Text::make(__('This invitation was sent to :email. Log out, then open the link again.', ['email' => $invitation->email])),
            ]);
        }

        if ($this->invitedUserExists($invitation)) {
            return $schema->components([
                Text::make(__('You already have an account with :email. Log in to accept the invitation.', ['email' => $invitation->email])),
                Actions::make([
                    Action::make('logIn')
                        ->label(__('Log in to accept'))
                        ->url(Filament::getLoginUrl()),
                ])->fullWidth(),
            ]);
        }

        return $schema->components([
            Text::make(__('Create your account with :email to accept the invitation.', ['email' => $invitation->email])),
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('register')
                ->footer([
                    Actions::make([
                        Action::make('register')
                            ->label(__('Create my account'))
                            ->submit('register'),
                    ])->fullWidth(),
                ]),
        ]);
    }

    public function register(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('Too many attempts. Try again in :seconds seconds.', ['seconds' => $exception->secondsUntilAvailable]))
                ->danger()
                ->send();

            return;
        }

        $invitation = $this->invitation();

        abort_if($invitation === null, 404);
        abort_if(Filament::auth()->check(), 403);
        abort_if($this->invitedUserExists($invitation), 403);

        $data = $this->form->getState();

        $user = DB::transaction(function () use ($data, $invitation): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $invitation->email,
                'password' => $data['password'],
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            $invitation->accept($user);

            return $user;
        });

        Filament::auth()->login($user);

        session()->regenerate();

        $this->redirect(Dashboard::getUrl(panel: 'app', tenant: $invitation->label));
    }

    protected function invitation(): ?Invitation
    {
        return Invitation::findPendingByToken($this->token);
    }

    protected function invitedUserExists(Invitation $invitation): bool
    {
        return User::query()->whereRaw('lower(email) = ?', [$invitation->email])->exists();
    }

    protected function acceptAndRedirect(Invitation $invitation, User $user): void
    {
        $invitation->accept($user);

        $this->redirect(Dashboard::getUrl(panel: 'app', tenant: $invitation->label));
    }
}
