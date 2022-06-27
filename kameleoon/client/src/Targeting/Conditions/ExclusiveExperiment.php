<?php
namespace Kameleoon\Targeting\Conditions;

use Kameleoon\Targeting\TargetingCondition;

class ExclusiveExperiment extends TargetingCondition
{
    const TYPE = "EXCLUSIVE_EXPERIMENT";
    
    public function check($arrayExperimentAndVariationStorage)
    {
        $experimentId = $arrayExperimentAndVariationStorage[0];
        $variationStorage = $arrayExperimentAndVariationStorage[1];
        $currentExperimentIdExist = isset($variationStorage) && isset($variationStorage[$experimentId]);
        return  !isset($variationStorage) ||
                count($variationStorage) == 0 ||
                count($variationStorage) == 1 && $currentExperimentIdExist;
    }
}
