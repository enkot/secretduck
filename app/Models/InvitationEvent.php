<?php

namespace App\Models;

use App\Enums\InvitationEventType;
use Carbon\CarbonInterface;
use Database\Factories\InvitationEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $invitation_id
 * @property int|null $recipient_id
 * @property int|null $guest_session_id
 * @property InvitationEventType $type
 * @property string|null $idempotency_key
 * @property array<string, scalar|null>|null $metadata
 * @property CarbonInterface $occurred_at
 */
#[Fillable([
    'invitation_id', 'recipient_id', 'guest_session_id', 'type', 'idempotency_key',
    'metadata', 'occurred_at',
])]
class InvitationEvent extends Model
{
    /** @use HasFactory<InvitationEventFactory> */
    use HasFactory;

    public $timestamps = false;

    /** @return BelongsTo<Invitation, $this> */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /** @return BelongsTo<InvitationRecipient, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(InvitationRecipient::class, 'recipient_id');
    }

    /** @return BelongsTo<GuestSession, $this> */
    public function guestSession(): BelongsTo
    {
        return $this->belongsTo(GuestSession::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => InvitationEventType::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
