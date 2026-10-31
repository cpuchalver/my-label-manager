<?php

namespace App\Models;

use App\Enums\LabelRole;
use Closure;
use Database\Factories\LabelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use LogicException;

#[Fillable(['name', 'slug', 'suspended_at'])]
class Label extends Model
{
    /** @use HasFactory<LabelFactory> */
    use HasFactory;

    /** @return BelongsToMany<User, $this> */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function isSuspended(): bool
    {
        return $this->suspended_at !== null;
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->whereKey($user->getKey())->exists();
    }

    public function owner(): ?User
    {
        return $this->members()
            ->get()
            ->first(fn (User $member): bool => $member->roleIn($this) === LabelRole::Owner);
    }

    public function addMember(User $user, LabelRole $role): void
    {
        $this->members()->syncWithoutDetaching([$user->getKey()]);

        $user->assignLabelRole($this, $role);
    }

    public function changeMemberRole(User $member, LabelRole $role): void
    {
        if ($role === LabelRole::Owner) {
            throw new LogicException('Ownership can only be obtained by a transfer.');
        }

        if ($member->roleIn($this) === LabelRole::Owner) {
            throw new LogicException('The role of the owner can only change through a transfer of ownership.');
        }

        $member->assignLabelRole($this, $role);
    }

    public function removeMember(User $member): void
    {
        if ($member->roleIn($this) === LabelRole::Owner) {
            throw new LogicException('The owner cannot be removed from the label.');
        }

        $member->assignLabelRole($this, null);

        $this->members()->detach($member->getKey());
    }

    public function transferOwnership(User $newOwner): void
    {
        if (! $this->hasMember($newOwner)) {
            throw new LogicException('Ownership can only be transferred to a member of the label.');
        }

        $currentOwner = $this->owner();

        if ($currentOwner?->is($newOwner)) {
            return;
        }

        DB::transaction(function () use ($currentOwner, $newOwner): void {
            $currentOwner?->assignLabelRole($this, LabelRole::Admin);

            $newOwner->assignLabelRole($this, LabelRole::Owner);
        });
    }

    /**
     * Run the callback with the roles and permissions of this label, then restore the previous label.
     *
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public function withinPermissionsTeam(Closure $callback): mixed
    {
        $previousTeamId = getPermissionsTeamId();

        setPermissionsTeamId($this);

        try {
            return $callback();
        } finally {
            setPermissionsTeamId($previousTeamId);
        }
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'suspended_at' => 'datetime',
        ];
    }
}
