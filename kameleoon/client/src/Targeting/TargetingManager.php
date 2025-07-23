<?php

declare(strict_types=1);

namespace Kameleoon\Targeting;

interface TargetingManager
{
    public function checkTargeting(string $visitorCode, ?int $containerID, ?TargetingSegment $segment): bool;
}
