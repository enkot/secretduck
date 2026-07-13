<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\InvitationRecipientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $public_id
 * @property int $invitation_id
 * @property string $name
 * @property string|null $email
 * @property string|null $greeting
 * @property int $max_guests
 * @property string $token_hash
 * @property string $token_ciphertext
 * @property int $token_version
 * @property CarbonInterface|null $expires_at
 * @property CarbonInterface|null $opened_at
 * @property CarbonInterface|null $last_opened_at
 * @property CarbonInterface|null $challenge_started_at
 * @property CarbonInterface|null $challenge_completed_at
 * @property CarbonInterface|null $revealed_at
 * @property CarbonInterface|null $attempt_window_started_at
 * @property CarbonInterface|null $challenge_locked_until
 * @property int $failed_attempts
 * @property int $hints_used
 * @property CarbonInterface|null $revoked_at
 * @property int|null $total
 * @property int|null $opened
 * @property int|null $completed
 * @property int|null $revealed
 * @property-read Invitation $invitation
 * @property-read Rsvp|null $rsvp
 */
#[Fillable([
    'public_id', 'invitation_id', 'name', 'email', 'greeting', 'max_guests',
    'token_hash', 'token_ciphertext', 'token_version', 'expires_at', 'opened_at',
    'last_opened_at', 'challenge_started_at', 'challenge_completed_at', 'revealed_at',
    'attempt_window_started_at', 'challenge_locked_until', 'failed_attempts',
    'hints_used', 'revoked_at',
])]
#[Hidden(['token_hash', 'token_ciphertext'])]
class InvitationRecipient extends Model
{
    /** @use HasFactory<InvitationRecipientFactory> */
    use HasFactory;

    /** @return BelongsTo<Invitation, $this> */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /** @return HasMany<GuestSession, $this> */
    public function guestSessions(): HasMany
    {
        return $this->hasMany(GuestSession::class, 'recipient_id');
    }

    /** @return HasOne<Rsvp, $this> */
    public function rsvp(): HasOne
    {
        return $this->hasOne(Rsvp::class, 'recipient_id');
    }

    /** @return HasMany<InvitationEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(InvitationEvent::class, 'recipient_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function hasActiveAccess(): bool
    {
        return $this->revoked_at === null && ! $this->isExpired() && $this->invitation->isGuestAvailable();
    }

    public function isUnlocked(): bool
    {
        return $this->challenge_completed_at !== null;
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'token_ciphertext' => 'encrypted',
            'token_version' => 'integer',
            'max_guests' => 'integer',
            'failed_attempts' => 'integer',
            'hints_used' => 'integer',
            'expires_at' => 'datetime',
            'opened_at' => 'datetime',
            'last_opened_at' => 'datetime',
            'challenge_started_at' => 'datetime',
            'challenge_completed_at' => 'datetime',
            'revealed_at' => 'datetime',
            'attempt_window_started_at' => 'datetime',
            'challenge_locked_until' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
