<?php

namespace App\Models;

use App\Enums\InvitationStatus;
use App\Enums\Theme;
use Carbon\CarbonInterface;
use Database\Factories\InvitationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $public_id
 * @property int $team_id
 * @property InvitationStatus $status
 * @property string|null $title
 * @property string|null $host_names
 * @property CarbonInterface|null $starts_at
 * @property string|null $timezone
 * @property string|null $venue_name
 * @property string|null $address
 * @property string|null $description
 * @property string|null $dress_code
 * @property CarbonInterface|null $rsvp_deadline_at
 * @property string|null $map_url
 * @property string|null $external_url
 * @property Theme $theme
 * @property string|null $accent_color
 * @property string|null $cover_image_path
 * @property string|null $reveal_heading
 * @property string|null $teaser_text
 * @property string|null $success_message
 * @property int $default_max_guests
 * @property CarbonInterface|null $access_expires_at
 * @property CarbonInterface|null $published_at
 * @property CarbonInterface|null $paused_at
 * @property CarbonInterface|null $archived_at
 * @property int $recipients_count
 * @property int $completed_count
 */
#[Fillable([
    'public_id', 'team_id', 'status', 'title', 'host_names', 'starts_at', 'timezone',
    'venue_name', 'address', 'description', 'dress_code', 'rsvp_deadline_at',
    'map_url', 'external_url', 'theme', 'accent_color', 'cover_image_path',
    'reveal_heading', 'teaser_text', 'success_message', 'default_max_guests',
    'access_expires_at', 'published_at', 'paused_at', 'archived_at',
])]
class Invitation extends Model
{
    /** @use HasFactory<InvitationFactory> */
    use HasFactory;

    /** @return BelongsTo<Team, $this> */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /** @return HasOne<Challenge, $this> */
    public function challenge(): HasOne
    {
        return $this->hasOne(Challenge::class);
    }

    /** @return HasMany<InvitationRecipient, $this> */
    public function recipients(): HasMany
    {
        return $this->hasMany(InvitationRecipient::class);
    }

    /** @return HasMany<InvitationEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(InvitationEvent::class);
    }

    public function effectiveAccessExpiresAt(): ?CarbonInterface
    {
        if ($this->access_expires_at instanceof CarbonInterface) {
            return $this->access_expires_at;
        }

        return $this->starts_at?->copy()->addDays(config('questinvite.default_access_expiry_days'));
    }

    public function isExpired(): bool
    {
        return $this->effectiveAccessExpiresAt()?->isPast() ?? false;
    }

    public function isGuestAvailable(): bool
    {
        return $this->status === InvitationStatus::Published
            && ! $this->isExpired()
            && $this->team()->exists();
    }

    public function rsvpClosesAt(): ?CarbonInterface
    {
        return $this->rsvp_deadline_at ?? $this->starts_at;
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => InvitationStatus::class,
            'theme' => Theme::class,
            'starts_at' => 'datetime',
            'rsvp_deadline_at' => 'datetime',
            'access_expires_at' => 'datetime',
            'published_at' => 'datetime',
            'paused_at' => 'datetime',
            'archived_at' => 'datetime',
            'default_max_guests' => 'integer',
        ];
    }
}
