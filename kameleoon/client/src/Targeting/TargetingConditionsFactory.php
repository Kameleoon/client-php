<?php
namespace Kameleoon\Targeting;

use Kameleoon\Targeting\Conditions\CustomDatum;

class TargetingConditionsFactory
{
    public function getCondition($targetingConditionType, $conditionData)
    {
        if ($targetingConditionType == "CUSTOM_DATUM") {
            $customDatumCondition = new CustomDatum();
            $customDatumCondition->setType($conditionData->targetingType);
            $customDatumCondition->setInclude($conditionData->isInclude);
            $customDatumCondition->setIndex(intval($conditionData->customDataIndex));
            $customDatumCondition->setOperator($conditionData->valueMatchType);
            $customDatumCondition->setValue($conditionData->value);
            return $customDatumCondition;
        }

        return null;
    }
}
