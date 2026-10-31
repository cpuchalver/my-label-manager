<?php

namespace App\Models;

use App\Enums\LabelRole;
use App\Notifications\LabelInvitation;
use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use LogicException;

#[Fillable(['email', 'role', 'invited_by_id', 'expires_at', 'accepted_at'])]
#[Hidden(['token_hash'])]
class Invitation extends Model
{
    /** @use HasFactory<InvitationFactory> */
    use HasFactory;

    public const ValidityInDays = 7;

    public static function findPendingByToken(string $token): ?self
    {
        return self::query()
            ->where('token_hash', self::hashToken($token))
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /** @return BelongsTo<Label, $this> */
    public function label(): BelongsTo
    {
        return $this->belongsTo(Label::class);
    }

    /** @return BelongsTo<User, $this> */
    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    public function isPending(): bool
    {
        if ($this->accepted_at !== null) {
            return false;
        }

        return $this->expires_at->isFuture();
    }

    public function isFor(User $user): bool
    {
        return Str::lower($user->email) === Str::lower($this->email);
    }

    /**
     * Generate a new single-use link, valid for seven days, and send it. Any previous link stops working.
     */
    public function send(): void
    {
        $token = Str::random(64);

        $this->forceFill([
            'token_hash' => self::hashToken($token),
            'expires_at' => now()->addDays(self::ValidityInDays),
        ])->save();

        Notification::route('mail', $this->email)->notify(new LabelInvitation($this, $token));
    }

    public function accept(User $user): void
    {
        if (! $this->isPending()) {
            throw new LogicException('This invitation is no longer pending.');
        }

        if (! $this->isFor($user)) {
            throw new LogicException('This invitation was sent to another email address.');
        }

        DB::transaction(function () use ($user): void {
            if (! $this->label->hasMember($user)) {
                $this->label->addMember($user, $this->role);
            }

            $this->forceFill(['accepted_at' => now()])->save();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => LabelRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }
}
