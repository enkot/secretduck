<?php

namespace Database\Factories;

use App\Enums\ChallengeType;
use App\Models\Challenge;
use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Challenge>
 */
class ChallengeFactory extends Factory
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
            'type' => ChallengeType::Trivia,
            'public_configuration' => [
                'question' => 'Where did we first meet?',
                'options' => [
                    ['id' => 'a', 'label' => 'At a coffee shop'],
                    ['id' => 'b', 'label' => 'At a rainy bus stop'],
                    ['id' => 'c', 'label' => 'At dinner'],
                ],
                'successMessage' => 'Exactly!',
                'failureMessage' => 'Not quite—try again.',
            ],
            'private_configuration' => ['correctOptionId' => 'b'],
            'max_attempts' => 5,
        ];
    }
}
