<?php

namespace Database\Factories;

use App\Enums\InvitationEventType;
use App\Models\Invitation;
use App\Models\InvitationEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvitationEvent>
 */
class InvitationEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'type' => InvitationEventType::LinkOpened,
            'occurred_at' => now(),
        ];
    }
}
