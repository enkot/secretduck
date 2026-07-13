<?php

namespace App\Models;

use App\Enums\ChallengeType;
use Database\Factories\ChallengeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $invitation_id
 * @property ChallengeType $type
 * @property array<string, mixed> $public_configuration
 * @property array<string, mixed> $private_configuration
 * @property int $max_attempts
 */
#[Fillable(['invitation_id', 'type', 'public_configuration', 'private_configuration', 'max_attempts'])]
#[Hidden(['private_configuration'])]
class Challenge extends Model
{
    /** @use HasFactory<ChallengeFactory> */
    use HasFactory;

    /** @return BelongsTo<Invitation, $this> */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => ChallengeType::class,
            'public_configuration' => 'array',
            'private_configuration' => 'encrypted:array',
            'max_attempts' => 'integer',
        ];
    }
}
