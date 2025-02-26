<?php

declare(strict_types=1);

namespace Kameleoon\Configuration;

class MEGroup
{
    private array $featureFlags;

    public function __construct(array $featureFlags)
    {
        usort($featureFlags, function ($a, $b) {
            return $a->id - $b->id;
        });
        $this->featureFlags = $featureFlags;
    }

    public function &getFeatureFlags(): array
    {
        return $this->featureFlags;
    }

    public function getFeatureFlagByHash(float $hash): FeatureFlag
    {
        $size = count($this->featureFlags);
        $idx = (int)($hash * $size);
        if ($idx >= $size) {
            $idx = $size - 1;
        }
        return $this->featureFlags[$idx];
    }
}
