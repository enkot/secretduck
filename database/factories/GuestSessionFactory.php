<?php

namespace Database\Factories;

use App\Models\GuestSession;
use App\Models\InvitationRecipient;
use App\ValueObjects\RecipientToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuestSession>
 */
class GuestSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $token = RecipientToken::generate();

        return [
            'recipient_id' => InvitationRecipient::factory(),
            'browser_token_hash' => $token->hash(),
            'recipient_token_version' => 1,
            'authorized_at' => now(),
            'last_seen_at' => now(),
            'expires_at' => now()->addMonth(),
        ];
    }
}
