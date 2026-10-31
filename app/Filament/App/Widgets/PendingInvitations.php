<?php

namespace App\Filament\App\Widgets;

use App\Enums\LabelPermission;
use App\Models\Invitation;
use App\Models\Label;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Livewire\Attributes\On;

class PendingInvitations extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can(LabelPermission::ManageMembers->value) ?? false;
    }

    #[On('invitation-sent')]
    public function refreshInvitations(): void
    {
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pending invitations')
            ->query($this->currentLabel()->pendingInvitations()->getQuery()->with('invitedBy'))
            ->columns([
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('role')
                    ->badge(),
                TextColumn::make('invitedBy.name')
                    ->label('Invited by'),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->since(),
            ])
            ->recordActions([
                Action::make('resendInvitation')
                    ->label('Resend')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->action(function (Invitation $record): void {
                        $record->send();

                        Notification::make()
                            ->title('Invitation sent again')
                            ->success()
                            ->send();
                    }),
                Action::make('cancelInvitation')
                    ->label('Cancel')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Invitation $record): void {
                        $record->delete();

                        Notification::make()
                            ->title('Invitation cancelled')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No pending invitations');
    }

    protected function currentLabel(): Label
    {
        $label = Filament::getTenant();

        abort_unless($label instanceof Label, 404);

        return $label;
    }
}
