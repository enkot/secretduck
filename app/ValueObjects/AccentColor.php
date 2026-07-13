<?php

namespace App\ValueObjects;

use InvalidArgumentException;

final readonly class AccentColor
{
    public function __construct(public string $hex)
    {
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $hex) !== 1) {
            throw new InvalidArgumentException('The accent color must be a six-digit hexadecimal color.');
        }
    }

    public function foreground(): string
    {
        [$red, $green, $blue] = [
            hexdec(substr($this->hex, 1, 2)),
            hexdec(substr($this->hex, 3, 2)),
            hexdec(substr($this->hex, 5, 2)),
        ];

        $luminance = (($red * 299) + ($green * 587) + ($blue * 114)) / 1000;

        return $luminance >= 145 ? '#111827' : '#FFFFFF';
    }
}
