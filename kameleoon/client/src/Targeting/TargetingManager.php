<?php

declare(strict_types=1);

namespace Kameleoon\Targeting;

use Kameleoon\Configuration\TargetingObject;

interface TargetingManager
{
    public function checkTargeting(string $visitorCode, int $containerID, TargetingObject $targetingObject): bool;
}
