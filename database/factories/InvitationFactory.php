<?php

namespace Database\Factories;

use App\Enums\InvitationStatus;
use App\Enums\Theme;
use App\Models\Invitation;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::ulid(),
            'team_id' => Team::factory(),
            'status' => InvitationStatus::Draft,
            'title' => fake()->sentence(3),
            'host_names' => fake()->name().' & '.fake()->name(),
            'starts_at' => now()->addMonth(),
            'timezone' => 'Europe/Kyiv',
            'venue_name' => fake()->company(),
            'address' => fake()->address(),
            'description' => fake()->paragraph(),
            'rsvp_deadline_at' => now()->addWeeks(2),
            'theme' => Theme::Elegant,
            'accent_color' => '#BE123C',
            'reveal_heading' => 'You are invited',
            'teaser_text' => 'A little quest stands between you and the details…',
            'success_message' => 'You found it!',
            'default_max_guests' => 2,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => InvitationStatus::Published, 'published_at' => now()]);
    }
}
