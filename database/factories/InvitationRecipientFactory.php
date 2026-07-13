<?php

namespace Database\Factories;

use App\Models\Invitation;
use App\Models\InvitationRecipient;
use App\ValueObjects\RecipientToken;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InvitationRecipient>
 */
class InvitationRecipientFactory extends Factory
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
            'public_id' => (string) Str::ulid(),
            'invitation_id' => Invitation::factory(),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'greeting' => 'A private invitation just for you.',
            'max_guests' => 2,
            'token_hash' => $token->hash(),
            'token_ciphertext' => $token->value,
            'token_version' => 1,
            'failed_attempts' => 0,
            'hints_used' => 0,
        ];
    }

    public function unlocked(): static
    {
        return $this->state(fn (): array => ['opened_at' => now()->subHour(), 'challenge_started_at' => now()->subMinutes(50), 'challenge_completed_at' => now()->subMinutes(45)]);
    }

    public function revoked(): static
    {
        return $this->state(fn (): array => ['revoked_at' => now()]);
    }
}
