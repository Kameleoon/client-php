<?php

declare(strict_types=1);

namespace Kameleoon\Hybrid;

interface HybridManager
{
    public function getEngineTrackingCode(?array $visitorVariationStorage): string;
}
