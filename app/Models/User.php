<?php

namespace App\Models;

use App\Enums\LabelRole;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasDefaultTenant, HasTenants, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasRoles;
    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;
    use Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->is_admin;
        }

        return $this->labels()->exists();
    }

    /** @return BelongsToMany<Label, $this> */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class)->withTimestamps();
    }

    /** @return BelongsTo<Label, $this> */
    public function latestLabel(): BelongsTo
    {
        return $this->belongsTo(Label::class, 'latest_label_id');
    }

    /** @return Collection<int, Label> */
    public function getTenants(Panel $panel): Collection
    {
        return $this->labels;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if (! $tenant instanceof Label) {
            return false;
        }

        return $this->labels()->whereKey($tenant->getKey())->exists();
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        $latestLabel = $this->latestLabel;

        if ($latestLabel === null) {
            return $this->labels()->first();
        }

        if (! $this->canAccessTenant($latestLabel)) {
            return $this->labels()->first();
        }

        return $latestLabel;
    }

    public function roleIn(Label $label): ?LabelRole
    {
        $roleName = $label->withinPermissionsTeam(function (): ?string {
            $this->unsetRelation('roles');

            $roleName = $this->getRoleNames()->first();

            return is_string($roleName) ? $roleName : null;
        });

        $this->unsetRelation('roles');

        if ($roleName === null) {
            return null;
        }

        return LabelRole::tryFrom($roleName);
    }

    /**
     * Give the user a single role in the label, or remove every role when null is given.
     */
    public function assignLabelRole(Label $label, ?LabelRole $role): void
    {
        $label->withinPermissionsTeam(function () use ($role): void {
            $this->unsetRelation('roles');

            $this->syncRoles($role === null ? [] : [$role->value]);
        });

        $this->unsetRelation('roles');
    }

    public function rememberLatestLabel(Label $label): void
    {
        if ($this->latest_label_id === $label->getKey()) {
            return;
        }

        $this->latestLabel()->associate($label)->save();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }
}
