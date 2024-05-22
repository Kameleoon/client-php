<?php

declare(strict_types=1);

namespace Kameleoon\Targeting;

use Kameleoon\Configuration\DataFile;
use Kameleoon\Configuration\TargetingObject;

interface TargetingManager
{
    public function setDataFile(DataFile $dataFile): void;

    public function checkTargeting(string $visitorCode, int $containerID, TargetingObject $targetingObject): bool;
}
