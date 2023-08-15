<?php

namespace Kameleoon\Targeting;

use Kameleoon\Targeting\Condition\BrowserCondition;
use Kameleoon\Targeting\Condition\ConversionCondition;
use Kameleoon\Targeting\Condition\CustomDatum;
use Kameleoon\Targeting\Condition\DeviceCondition;
use Kameleoon\Targeting\Condition\ExclusiveExperiment;
use Kameleoon\Targeting\Condition\PageTitleCondition;
use Kameleoon\Targeting\Condition\PageUrlCondition;
use Kameleoon\Targeting\Condition\SdkLanguageCondition;
use Kameleoon\Targeting\Condition\TargetedExperiment;
use Kameleoon\Targeting\Condition\TargetingCondition;
use Kameleoon\Targeting\Condition\UnknownCondition;
use Kameleoon\Targeting\Condition\VisitorCodeCondition;

class TargetingConditionsFactory
{
    public static function getCondition($conditionData): ?TargetingCondition
    {
        switch ($conditionData->targetingType) {
            case CustomDatum::TYPE:
                return new CustomDatum($conditionData);

            case TargetedExperiment::TYPE:
                return new TargetedExperiment($conditionData);

            case ExclusiveExperiment::TYPE:
                return new ExclusiveExperiment($conditionData);

            case VisitorCodeCondition::TYPE:
                return new VisitorCodeCondition($conditionData);

            case PageUrlCondition::TYPE:
                return new PageUrlCondition($conditionData);

            case PageTitleCondition::TYPE:
                return new PageTitleCondition($conditionData);

            case DeviceCondition::TYPE:
                return new DeviceCondition($conditionData);

            case BrowserCondition::TYPE:
                return new BrowserCondition($conditionData);

            case ConversionCondition::TYPE:
                return new ConversionCondition($conditionData);

            case SdkLanguageCondition::TYPE:
                return new SdkLanguageCondition($conditionData);

            default:
                return new UnknownCondition($conditionData);
        }
    }
}
