<?php

namespace App\Challenges;

use App\Challenges\Drivers\ScratchChallengeDriver;
use App\Challenges\Drivers\SudokuChallengeDriver;
use App\Challenges\Drivers\TriviaChallengeDriver;
use App\Enums\ChallengeType;
use InvalidArgumentException;

final class ChallengeManager
{
    public function __construct(
        private readonly ScratchChallengeDriver $scratch,
        private readonly TriviaChallengeDriver $trivia,
        private readonly SudokuChallengeDriver $sudoku,
    ) {}

    public function driver(ChallengeType $type): ChallengeDriver
    {
        return match ($type) {
            ChallengeType::Scratch => $this->scratch,
            ChallengeType::Trivia => $this->trivia,
            ChallengeType::Sudoku => $this->sudoku,
        };
    }

    public function hintableDriver(ChallengeType $type): HintableChallengeDriver
    {
        $driver = $this->driver($type);

        if (! $driver instanceof HintableChallengeDriver) {
            throw new InvalidArgumentException('This challenge does not support hints.');
        }

        return $driver;
    }
}
