<?php
namespace Kameleoon\Targeting;

use Kameleoon\Targeting\Conditions\CustomDatum;
use Kameleoon\Targeting\Conditions\ExclusiveExperiment;
use Kameleoon\Targeting\Conditions\TargetedExperiment;

class TargetingConditionsFactory
{

    public function getCondition($targetingConditionType, $conditionData)
    {
        switch ($targetingConditionType) {
            case CustomDatum::TYPE:
                $customDatumCondition = new CustomDatum();
                $customDatumCondition->setType($conditionData->targetingType);
                $customDatumCondition->setInclude($conditionData->isInclude);
                $customDatumCondition->setIndex(intval($conditionData->customDataIndex));
                $customDatumCondition->setOperator($conditionData->valueMatchType);
                $customDatumCondition->setValue($conditionData->value);
                return $customDatumCondition;
            case TargetedExperiment::TYPE:
                $targetedExperimentCondition = new TargetedExperiment();
                $targetedExperimentCondition->setType($conditionData->targetingType);
                $targetedExperimentCondition->setInclude($conditionData->isInclude);
                $targetedExperimentCondition->setExperiment($conditionData->experiment);
                if(isset($conditionData->variation)) {
                    $targetedExperimentCondition->setVariation($conditionData->variation);
                }
                $targetedExperimentCondition->setOperator($conditionData->variationMatchType);
                return $targetedExperimentCondition;
            case ExclusiveExperiment::TYPE:
                $exclusiveExperimentCondition = new ExclusiveExperiment();
                $exclusiveExperimentCondition->setType($conditionData->targetingType);
                $exclusiveExperimentCondition->setInclude(true);
                return $exclusiveExperimentCondition;
            default:
                break;
        }
        return null;
    }
}
