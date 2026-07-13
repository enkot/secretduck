<?php

namespace Database\Factories;

use App\Enums\RsvpResponse;
use App\Models\Rsvp;
use App\Models\RsvpRevision;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RsvpRevision>
 */
class RsvpRevisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rsvp_id' => Rsvp::factory(),
            'respondent_name' => fake()->name(),
            'response' => RsvpResponse::Attending,
            'guest_count' => 1,
            'dietary_notes' => null,
            'message' => null,
            'created_at' => now(),
        ];
    }
}
