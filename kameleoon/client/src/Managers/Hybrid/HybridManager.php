<?php

declare(strict_types=1);

namespace Kameleoon\Managers\Hybrid;

interface HybridManager
{
    public function getEngineTrackingCode(?array $variations): string;
}
