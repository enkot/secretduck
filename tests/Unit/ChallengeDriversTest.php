<?php

use App\Challenges\Drivers\ScratchChallengeDriver;
use App\Challenges\Drivers\SudokuChallengeDriver;
use App\Challenges\Drivers\TriviaChallengeDriver;
use App\Challenges\SudokuTemplateRegistry;
use App\Enums\ChallengeType;
use App\Models\Challenge;
use App\ValueObjects\RecipientToken;
use Tests\TestCase;

uses(TestCase::class);

test('recipient tokens are high entropy URL-safe values', function () {
    $tokens = collect(range(1, 100))->map(fn () => RecipientToken::generate());

    expect($tokens->pluck('value')->unique())->toHaveCount(100)
        ->and($tokens->every(fn (RecipientToken $token): bool => strlen($token->value) >= 43))->toBeTrue()
        ->and($tokens->every(fn (RecipientToken $token): bool => preg_match('/^[A-Za-z0-9_-]+$/', $token->value) === 1))->toBeTrue()
        ->and($tokens->first()->fragmentUrl('https://example.test/open/01'))->toContain('#t=')
        ->and($tokens->first()->hash())->toHaveLength(64);
});

test('trivia separates the correct answer from guest configuration', function () {
    $driver = new TriviaChallengeDriver;
    $normalized = $driver->normalizeHostConfiguration([
        'question' => 'Where did we meet?',
        'options' => [
            ['id' => 'one', 'label' => 'Cafe'],
            ['id' => 'two', 'label' => 'Station'],
        ],
        'correctOptionId' => 'two',
        'failureMessage' => 'Try again.',
    ]);
    $challenge = new Challenge([
        'type' => ChallengeType::Trivia,
        'public_configuration' => $normalized['public'],
        'private_configuration' => $normalized['private'],
    ]);

    expect($normalized['public'])->not->toHaveKey('correctOptionId')
        ->and($normalized['private'])->toBe(['correctOptionId' => 'two'])
        ->and($driver->verify($challenge, ['optionId' => 'one'])->completed)->toBeFalse()
        ->and($driver->verify($challenge, ['optionId' => 'two'])->completed)->toBeTrue();
});

test('every curated Sudoku template has valid rows columns regions and matching givens', function () {
    $registry = new SudokuTemplateRegistry;

    expect($registry->all())->toHaveCount(12);

    foreach ($registry->all() as $template) {
        expect($template['solution'])->toHaveCount(16);
        foreach (range(0, 3) as $index) {
            expect(array_values(array_unique(array_slice($template['solution'], $index * 4, 4))))->toHaveCount(4);
            expect(array_unique([$template['solution'][$index], $template['solution'][$index + 4], $template['solution'][$index + 8], $template['solution'][$index + 12]]))->toHaveCount(4);
        }
        foreach ([0, 2, 8, 10] as $start) {
            expect(array_unique([$template['solution'][$start], $template['solution'][$start + 1], $template['solution'][$start + 4], $template['solution'][$start + 5]]))->toHaveCount(4);
        }
        foreach ($template['givens'] as $index => $given) {
            if ($given !== null) {
                expect($given)->toBe($template['solution'][$index]);
            }
        }
    }
});

test('Sudoku solution stays private and exact solution completes', function () {
    $driver = new SudokuChallengeDriver(new SudokuTemplateRegistry);
    $normalized = $driver->normalizeHostConfiguration(['template' => 'garden-path', 'allowedHints' => 1]);
    $challenge = new Challenge([
        'type' => ChallengeType::Sudoku,
        'public_configuration' => $normalized['public'],
        'private_configuration' => $normalized['private'],
    ]);

    expect($normalized['public'])->not->toHaveKey('solution')
        ->and($driver->verify($challenge, ['grid' => $normalized['private']['solution']])->completed)->toBeTrue();
});

test('scratch completion requires the explicit handshake', function () {
    $driver = new ScratchChallengeDriver;
    $challenge = new Challenge([
        'type' => ChallengeType::Scratch,
        'public_configuration' => ['prompt' => 'Scratch', 'threshold' => 65],
        'private_configuration' => [],
    ]);

    expect($driver->verify($challenge, ['thresholdReached' => true])->completed)->toBeTrue();
});
