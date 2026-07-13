<?php

namespace App\ValueObjects;

use Carbon\CarbonInterface;

final readonly class ChallengeResult
{
    public function __construct(
        public bool $completed,
        public string $message,
        public ?int $attemptsRemaining = null,
        public ?CarbonInterface $lockedUntil = null,
    ) {}

    /** @return array{completed: bool, message: string, attemptsRemaining: int|null, lockedUntil: string|null} */
    public function toArray(): array
    {
        return [
            'completed' => $this->completed,
            'message' => $this->message,
            'attemptsRemaining' => $this->attemptsRemaining,
            'lockedUntil' => $this->lockedUntil?->toIso8601String(),
        ];
    }
}
