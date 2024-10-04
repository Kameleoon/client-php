<?php

declare(strict_types=1);

namespace Kameleoon\Managers\Tracking;

interface TrackingManager
{
    public function trackVisitor(string $visitorCode): void;

    public function trackAll(): void;
}
