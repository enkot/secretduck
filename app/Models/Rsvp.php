<?php

namespace App\Models;

use App\Enums\RsvpResponse;
use Carbon\CarbonInterface;
use Database\Factories\RsvpFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $recipient_id
 * @property string $respondent_name
 * @property RsvpResponse $response
 * @property int $guest_count
 * @property string|null $dietary_notes
 * @property string|null $message
 * @property CarbonInterface $submitted_at
 */
#[Fillable(['recipient_id', 'respondent_name', 'response', 'guest_count', 'dietary_notes', 'message', 'submitted_at'])]
class Rsvp extends Model
{
    /** @use HasFactory<RsvpFactory> */
    use HasFactory;

    /** @return BelongsTo<InvitationRecipient, $this> */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(InvitationRecipient::class, 'recipient_id');
    }

    /** @return HasMany<RsvpRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(RsvpRevision::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'response' => RsvpResponse::class,
            'guest_count' => 'integer',
            'dietary_notes' => 'encrypted',
            'message' => 'encrypted',
            'submitted_at' => 'datetime',
        ];
    }
}
