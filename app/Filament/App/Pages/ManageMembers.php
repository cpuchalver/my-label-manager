<?php

namespace App\Filament\App\Pages;

use App\Enums\LabelPermission;
use App\Enums\LabelRole;
use App\Models\Label;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ManageMembers extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Members';

    protected static ?string $title = 'Members';

    protected static ?string $slug = 'members';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(LabelPermission::ManageMembers->value) ?? false;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(User::query()->whereRelation('labels', 'labels.id', $this->currentLabel()->getKey()))
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('role')
                    ->badge()
                    ->state(fn (User $record): ?string => $record->roleIn($this->currentLabel())?->getLabel()),
            ])
            ->recordActions([
                Action::make('changeRole')
                    ->label('Change role')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->visible(fn (User $record): bool => $this->canManage($record))
                    ->fillForm(fn (User $record): array => [
                        'role' => $record->roleIn($this->currentLabel())?->value,
                    ])
                    ->schema([
                        Select::make('role')
                            ->options(LabelRole::assignableOptions())
                            ->required(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $this->currentLabel()->changeMemberRole($record, LabelRole::from((string) $data['role']));

                        Notification::make()
                            ->title('Role updated')
                            ->success()
                            ->send();
                    }),
                Action::make('transferOwnership')
                    ->label('Make owner')
                    ->icon(Heroicon::OutlinedKey)
                    ->requiresConfirmation()
                    ->modalDescription('This member becomes the owner of the label, and you become an administrator.')
                    ->visible(fn (User $record): bool => $this->canTransferOwnershipTo($record))
                    ->action(function (User $record): void {
                        $this->currentLabel()->transferOwnership($record);

                        Notification::make()
                            ->title('Ownership transferred')
                            ->success()
                            ->send();
                    }),
                Action::make('removeMember')
                    ->label('Remove')
                    ->icon(Heroicon::OutlinedUserMinus)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $this->canManage($record))
                    ->action(function (User $record): void {
                        $this->currentLabel()->removeMember($record);

                        Notification::make()
                            ->title('Member removed')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    protected function currentLabel(): Label
    {
        $label = Filament::getTenant();

        abort_unless($label instanceof Label, 404);

        return $label;
    }

    protected function canManage(User $member): bool
    {
        if ($member->is(auth()->user())) {
            return false;
        }

        return $member->roleIn($this->currentLabel()) !== LabelRole::Owner;
    }

    protected function canTransferOwnershipTo(User $member): bool
    {
        if (! auth()->user()?->can(LabelPermission::TransferOwnership->value)) {
            return false;
        }

        if ($member->is(auth()->user())) {
            return false;
        }

        return $member->roleIn($this->currentLabel()) === LabelRole::Admin;
    }
}
