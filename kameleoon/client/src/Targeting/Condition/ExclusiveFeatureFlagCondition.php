<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

class ExclusiveFeatureFlagCondition extends TargetingCondition
{
    const TYPE = "EXCLUSIVE_FEATURE_FLAG";

    public function check($data): bool
    {
        if (!is_array($data)) {
            return false;
        }
        $currentExperimentId = $data[0] ?? -1;
        $variations = $data[1] ?? [];
        return (count($variations) == 0) ||
            ((count($variations) == 1) && array_key_exists($currentExperimentId, $variations));
    }
}
