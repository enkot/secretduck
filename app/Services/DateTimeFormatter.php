<?php

namespace App\Services;

use Carbon\CarbonInterface;

final class DateTimeFormatter
{
    public function dateTime(CarbonInterface $value, ?string $timeZone = null): string
    {
        return $value
            ->copy()
            ->setTimezone($this->timeZone($timeZone))
            ->format('j M Y, H:i');
    }

    public function date(CarbonInterface $value, ?string $timeZone = null): string
    {
        return $value
            ->copy()
            ->setTimezone($this->timeZone($timeZone))
            ->format('j M Y');
    }

    private function timeZone(?string $timeZone): string
    {
        return $timeZone ?: (string) config('app.timezone', 'UTC');
    }
}
