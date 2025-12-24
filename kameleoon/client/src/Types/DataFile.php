<?php

declare(strict_types=1);

namespace Kameleoon\Types;

use Kameleoon\Helpers\StringHelper;

class DataFile
{
    /**
     * @var array<string, FeatureFlag>
     */
    public array $featureFlags;

    /**
     * @internal
     * @param array<string, FeatureFlag> $featureFlags
     */
    public function __construct(array $featureFlags)
    {
        $this->featureFlags = $featureFlags;
    }

    public function __toString(): string
    {
        $featureFlags = StringHelper::sarray($this->featureFlags);
        return "DataFile{featureFlags:$featureFlags}";
    }
}
