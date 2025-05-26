<?php

declare(strict_types=1);

namespace Kameleoon\Data;

final class Visit
{
    private int $timeStarted;
    private int $timeLastActivity;

    public function __construct(int $timeStarted, ?int $timeLastActivity = null)
    {
        $this->timeStarted = $timeStarted;
        $this->timeLastActivity = $timeLastActivity ?? $timeStarted;
    }

    public function getTimeStarted(): int
    {
        return $this->timeStarted;
    }

    public function getTimeLastActivity(): int
    {
        return $this->timeLastActivity;
    }

    public function __toString(): string
    {
        return "Visit{" .
            "timeStarted:" . $this->timeStarted .
            ",timeLastActivity:" . $this->timeLastActivity .
            "}";
    }
}
