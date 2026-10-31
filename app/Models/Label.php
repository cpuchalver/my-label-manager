<?php

namespace App\Models;

use App\Enums\LabelRole;
use App\Events\LabelActivityUpdated;
use Closure;
use Database\Factories\LabelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    /** @return HasMany<Invitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /** @return HasMany<Invitation, $this> */
    public function pendingInvitations(): HasMany
    {
        return $this->invitations()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now());
    }

    public function hasMemberWithEmail(string $email): bool
    {
        return $this->members()->whereRaw('lower(users.email) = ?', [Str::lower(trim($email))])->exists();
    }

    /**
     * Invite someone by email. A pending invitation to the same address is replaced, so its link stops working.
     */
    public function inviteMember(string $email, LabelRole $role, ?User $invitedBy = null): Invitation
    {
        $email = Str::lower(trim($email));

        if ($role === LabelRole::Owner) {
            throw new LogicException('Ownership can only be obtained by a transfer.');
        }

        if ($this->hasMemberWithEmail($email)) {
            throw new LogicException('This person is already a member of the label.');
        }

        $this->invitations()
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->delete();

        $invitation = $this->invitations()->make([
            'email' => $email,
            'role' => $role,
            'invited_by_id' => $invitedBy?->getKey(),
        ]);

        $invitation->send();

        return $invitation;
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

        $this->broadcastActivity();
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

        $this->broadcastActivity();
    }

    public function removeMember(User $member): void
    {
        if ($member->roleIn($this) === LabelRole::Owner) {
            throw new LogicException('The owner cannot be removed from the label.');
        }

        $member->assignLabelRole($this, null);

        $this->members()->detach($member->getKey());

        $this->broadcastActivity();
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

        $this->broadcastActivity();
    }

    /** @return Collection<int, User> */
    public function administrators(): Collection
    {
        return $this->members()
            ->get()
            ->filter(fn (User $member): bool => in_array($member->roleIn($this), [LabelRole::Owner, LabelRole::Admin], true))
            ->values();
    }

    /**
     * Tell the open pages of the label that its data changed.
     */
    public function broadcastActivity(): void
    {
        LabelActivityUpdated::dispatch($this);
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
