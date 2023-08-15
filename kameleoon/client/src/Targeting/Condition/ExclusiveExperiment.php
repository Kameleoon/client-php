<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

class ExclusiveExperiment extends TargetingCondition
{
    const TYPE = "EXCLUSIVE_EXPERIMENT";

    public function check($arrayExperimentAndVariationStorage): bool
    {
        $experimentId = $arrayExperimentAndVariationStorage[0];
        $variationStorage = $arrayExperimentAndVariationStorage[1];
        $currentExperimentIdExist = isset($variationStorage) && isset($variationStorage[$experimentId]);
        return
            !isset($variationStorage) ||
            empty($variationStorage) ||
            count($variationStorage) == 1 && $currentExperimentIdExist;
    }
}
