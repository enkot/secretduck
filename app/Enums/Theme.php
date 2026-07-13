<?php

namespace App\Enums;

enum Theme: string
{
    case Elegant = 'elegant';
    case Romantic = 'romantic';
    case Minimal = 'minimal';
    case Playful = 'playful';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
