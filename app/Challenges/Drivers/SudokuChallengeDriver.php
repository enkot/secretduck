<?php

namespace App\Challenges\Drivers;

use App\Challenges\HintableChallengeDriver;
use App\Challenges\SudokuTemplateRegistry;
use App\Enums\ChallengeType;
use App\Models\Challenge;
use App\Models\InvitationRecipient;
use App\ValueObjects\ChallengeResult;
use Illuminate\Validation\ValidationException;

final readonly class SudokuChallengeDriver implements HintableChallengeDriver
{
    public function __construct(private SudokuTemplateRegistry $templates) {}

    public function type(): ChallengeType
    {
        return ChallengeType::Sudoku;
    }

    public function normalizeHostConfiguration(array $configuration): array
    {
        $template = $this->templates->get((string) ($configuration['template'] ?? 'garden-path'));
        $allowedHints = (int) ($configuration['allowedHints'] ?? 1);

        if ($allowedHints < 0 || $allowedHints > 2) {
            throw ValidationException::withMessages(['configuration.allowedHints' => 'Sudoku may allow zero, one, or two hints.']);
        }

        return [
            'public' => [
                'template' => $template['key'],
                'label' => $template['label'],
                'difficulty' => $template['difficulty'],
                'givens' => $template['givens'],
                'allowedHints' => $allowedHints,
            ],
            'private' => ['solution' => $template['solution']],
        ];
    }

    public function publicState(Challenge $challenge, InvitationRecipient $recipient): array
    {
        return [
            ...$challenge->public_configuration,
            'hintsRemaining' => max(0, (int) $challenge->public_configuration['allowedHints'] - $recipient->hints_used),
        ];
    }

    public function verify(Challenge $challenge, array $submission): ChallengeResult
    {
        $grid = $this->validatedGrid($submission['grid'] ?? null);
        $givens = $challenge->public_configuration['givens'];

        foreach ($givens as $index => $given) {
            if ($given !== null && $grid[$index] !== $given) {
                throw ValidationException::withMessages(['grid' => 'The original Sudoku clues cannot be changed.']);
            }
        }

        if (! $this->hasValidGroups($grid)) {
            return new ChallengeResult(false, 'A row, column, or box still needs attention.');
        }

        return new ChallengeResult(
            $grid === $challenge->private_configuration['solution'],
            $grid === $challenge->private_configuration['solution'] ? 'Puzzle solved!' : 'That grid is not the saved solution.',
        );
    }

    public function hint(Challenge $challenge, InvitationRecipient $recipient, array $submission): array
    {
        $allowedHints = (int) $challenge->public_configuration['allowedHints'];

        if ($recipient->hints_used >= $allowedHints) {
            throw ValidationException::withMessages(['hint' => 'No hints remain for this puzzle.']);
        }

        $grid = $submission['grid'] ?? [];
        $givens = $challenge->public_configuration['givens'];
        $solution = $challenge->private_configuration['solution'];

        if (! is_array($grid) || count($grid) !== 16) {
            throw ValidationException::withMessages(['grid' => 'Send the current 16-cell grid to request a hint.']);
        }

        foreach ($solution as $index => $value) {
            if ($givens[$index] === null && ($grid[$index] ?? null) !== $value) {
                return ['index' => $index, 'value' => $value];
            }
        }

        throw ValidationException::withMessages(['hint' => 'The puzzle is already complete.']);
    }

    /** @return array<int, int> */
    private function validatedGrid(mixed $grid): array
    {
        if (! is_array($grid) || count($grid) !== 16) {
            throw ValidationException::withMessages(['grid' => 'The Sudoku grid must contain exactly 16 cells.']);
        }

        $normalized = array_map(fn (mixed $value): int => filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : 0, array_values($grid));

        if (collect($normalized)->contains(fn (int $value): bool => $value < 1 || $value > 4)) {
            throw ValidationException::withMessages(['grid' => 'Every Sudoku cell must contain a number from 1 to 4.']);
        }

        return $normalized;
    }

    /** @param array<int, int> $grid */
    private function hasValidGroups(array $grid): bool
    {
        $valid = [1, 2, 3, 4];

        for ($index = 0; $index < 4; $index++) {
            $row = array_slice($grid, $index * 4, 4);
            $column = [$grid[$index], $grid[$index + 4], $grid[$index + 8], $grid[$index + 12]];
            sort($row);
            sort($column);

            if ($row !== $valid || $column !== $valid) {
                return false;
            }
        }

        foreach ([0, 2, 8, 10] as $start) {
            $region = [$grid[$start], $grid[$start + 1], $grid[$start + 4], $grid[$start + 5]];
            sort($region);

            if ($region !== $valid) {
                return false;
            }
        }

        return true;
    }
}
