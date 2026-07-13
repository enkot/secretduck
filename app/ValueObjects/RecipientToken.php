<?php

namespace App\ValueObjects;

use Illuminate\Support\Str;

final readonly class RecipientToken
{
    public function __construct(public string $value) {}

    public static function generate(): self
    {
        return new self(Str::of(base64_encode(random_bytes(32)))
            ->replace(['+', '/', '='], ['-', '_', ''])
            ->toString());
    }

    public function hash(): string
    {
        return hash('sha256', $this->value);
    }

    public function fragmentUrl(string $baseUrl): string
    {
        return rtrim($baseUrl, '#').'#t='.$this->value;
    }
}
