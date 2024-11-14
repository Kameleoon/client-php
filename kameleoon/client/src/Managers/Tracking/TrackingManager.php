<?php

declare(strict_types=1);

namespace Kameleoon\Managers\Tracking;

interface TrackingManager
{
    public function trackVisitor(string $visitorCode, bool $instant = false, ?int $timeout = null): void;

    public function trackAll(bool $instant = false, ?int $timeout = null): void;
}
