<?php

namespace Database\Factories;

use App\Enums\RsvpResponse;
use App\Models\InvitationRecipient;
use App\Models\Rsvp;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rsvp>
 */
class RsvpFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipient_id' => InvitationRecipient::factory(),
            'respondent_name' => fake()->name(),
            'response' => RsvpResponse::Attending,
            'guest_count' => 1,
            'dietary_notes' => null,
            'message' => fake()->sentence(),
            'submitted_at' => now(),
        ];
    }
}
