<?php

namespace App\Models;

use Database\Factories\UserFactory;
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

#[Fillable(['name', 'email', 'password', 'is_admin'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasDefaultTenant, HasTenants, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

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
