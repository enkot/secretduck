<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\GuestSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $recipient_id
 * @property string $browser_token_hash
 * @property int $recipient_token_version
 * @property CarbonInterface $authorized_at
 * @property CarbonInterface $last_seen_at
 * @property CarbonInterface $expires_at
 * @property CarbonInterface|null $revoked_at
 * @property-read InvitationRecipient $recipient
 */
#[Fillable([
    'recipient_id', 'browser_token_hash', 'recipient_token_version', 'authorized_at',
    'last_seen_at', 'expires_at', 'revoked_at',
])]
class GuestSession extends Model
{
    /** @use HasFactory<GuestSessionFactory> */
    use HasFactory;

    /** @return BelongsTo<InvitationRecipient, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(InvitationRecipient::class, 'recipient_id');
    }

    /** @return HasMany<InvitationEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(InvitationEvent::class);
    }

    public function isActiveFor(InvitationRecipient $recipient): bool
    {
        return $this->revoked_at === null
            && $this->expires_at->isFuture()
            && $this->recipient_token_version === $recipient->token_version;
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'recipient_token_version' => 'integer',
            'authorized_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
