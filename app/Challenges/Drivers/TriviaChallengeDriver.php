<?php

namespace App\Challenges\Drivers;

use App\Challenges\ChallengeDriver;
use App\Enums\ChallengeType;
use App\Models\Challenge;
use App\Models\InvitationRecipient;
use App\ValueObjects\ChallengeResult;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class TriviaChallengeDriver implements ChallengeDriver
{
    public function type(): ChallengeType
    {
        return ChallengeType::Trivia;
    }

    public function normalizeHostConfiguration(array $configuration): array
    {
        $question = trim((string) ($configuration['question'] ?? ''));
        $rawOptions = $configuration['options'] ?? [];
        $rawOptions = is_array($rawOptions) ? $rawOptions : [];
        $options = array_map(function (mixed $option): array {
            $option = is_array($option) ? $option : [];

            return [
                'id' => (string) ($option['id'] ?? Str::ulid()),
                'label' => trim((string) ($option['label'] ?? '')),
            ];
        }, array_values($rawOptions));

        $correctOptionId = (string) ($configuration['correctOptionId'] ?? '');

        if ($question === '' || mb_strlen($question) > 300) {
            throw ValidationException::withMessages(['configuration.question' => 'Enter a question up to 300 characters.']);
        }

        $hasInvalidOption = array_any($options, fn (array $option): bool => $option['label'] === '' || mb_strlen($option['label']) > 160);

        if (count($options) < 2 || count($options) > 6 || $hasInvalidOption) {
            throw ValidationException::withMessages(['configuration.options' => 'Provide between two and six non-empty answers.']);
        }

        if (! in_array($correctOptionId, array_column($options, 'id'), true)) {
            throw ValidationException::withMessages(['configuration.correctOptionId' => 'Choose the correct answer.']);
        }

        return [
            'public' => [
                'question' => $question,
                'options' => $options,
                'successMessage' => mb_substr((string) ($configuration['successMessage'] ?? 'That is it!'), 0, 300),
                'failureMessage' => mb_substr((string) ($configuration['failureMessage'] ?? 'Not quite—try again.'), 0, 300),
            ],
            'private' => ['correctOptionId' => $correctOptionId],
        ];
    }

    public function publicState(Challenge $challenge, InvitationRecipient $recipient): array
    {
        return $challenge->public_configuration;
    }

    public function verify(Challenge $challenge, array $submission): ChallengeResult
    {
        $optionId = (string) ($submission['optionId'] ?? '');
        $rawOptions = $challenge->public_configuration['options'] ?? [];
        $rawOptions = is_array($rawOptions) ? $rawOptions : [];
        $validIds = array_map(
            fn (mixed $option): string => is_array($option) ? (string) ($option['id'] ?? '') : '',
            $rawOptions,
        );

        if ($optionId === '' || ! in_array($optionId, $validIds, true)) {
            throw ValidationException::withMessages(['optionId' => 'Choose one of the available answers.']);
        }

        $completed = hash_equals((string) $challenge->private_configuration['correctOptionId'], $optionId);

        return new ChallengeResult(
            $completed,
            (string) $challenge->public_configuration[$completed ? 'successMessage' : 'failureMessage'],
        );
    }
}
