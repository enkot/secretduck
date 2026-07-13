<?php

namespace App\Models;

use App\Enums\RsvpResponse;
use Carbon\CarbonInterface;
use Database\Factories\RsvpRevisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $rsvp_id
 * @property int|null $guest_session_id
 * @property string $respondent_name
 * @property RsvpResponse $response
 * @property int $guest_count
 * @property string|null $dietary_notes
 * @property string|null $message
 * @property CarbonInterface $created_at
 */
#[Fillable([
    'rsvp_id', 'guest_session_id', 'respondent_name', 'response', 'guest_count',
    'dietary_notes', 'message', 'created_at',
])]
class RsvpRevision extends Model
{
    /** @use HasFactory<RsvpRevisionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /** @return BelongsTo<Rsvp, $this> */
    public function rsvp(): BelongsTo
    {
        return $this->belongsTo(Rsvp::class);
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
            'response' => RsvpResponse::class,
            'guest_count' => 'integer',
            'dietary_notes' => 'encrypted',
            'message' => 'encrypted',
            'created_at' => 'datetime',
        ];
    }
}
