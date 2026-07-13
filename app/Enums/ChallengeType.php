<?php

namespace App\Enums;

enum ChallengeType: string
{
    case Scratch = 'scratch';
    case Trivia = 'trivia';
    case Sudoku = 'sudoku';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
