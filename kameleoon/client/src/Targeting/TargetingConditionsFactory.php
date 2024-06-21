<?php

namespace Kameleoon\Targeting;

use Kameleoon\Targeting\Condition\BrowserCondition;
use Kameleoon\Targeting\Condition\ConversionCondition;
use Kameleoon\Targeting\Condition\CookieCondition;
use Kameleoon\Targeting\Condition\CustomDatum;
use Kameleoon\Targeting\Condition\DeviceCondition;
use Kameleoon\Targeting\Condition\ExclusiveFeatureFlagCondition;
use Kameleoon\Targeting\Condition\GeolocationCondition;
use Kameleoon\Targeting\Condition\OperatingSystemCondition;
use Kameleoon\Targeting\Condition\PageTitleCondition;
use Kameleoon\Targeting\Condition\PageUrlCondition;
use Kameleoon\Targeting\Condition\PageViewNumberCondition;
use Kameleoon\Targeting\Condition\PreviousPageCondition;
use Kameleoon\Targeting\Condition\SdkLanguageCondition;
use Kameleoon\Targeting\Condition\SegmentCondition;
use Kameleoon\Targeting\Condition\TargetFeatureFlagCondition;
use Kameleoon\Targeting\Condition\TargetingCondition;
use Kameleoon\Targeting\Condition\TimeElapsedSinceVisitCondition;
use Kameleoon\Targeting\Condition\UnknownCondition;
use Kameleoon\Targeting\Condition\VisitNumberTodayCondition;
use Kameleoon\Targeting\Condition\VisitNumberTotalCondition;
use Kameleoon\Targeting\Condition\VisitorCodeCondition;
use Kameleoon\Targeting\Condition\VisitorNewReturnCondition;
use Kameleoon\Targeting\Condition\KcsHeatRangeCondition;

class TargetingConditionsFactory
{
    public static function getCondition($conditionData): ?TargetingCondition
    {
        switch ($conditionData->targetingType) {
            case CustomDatum::TYPE:
                return new CustomDatum($conditionData);

            case TargetFeatureFlagCondition::TYPE:
                return new TargetFeatureFlagCondition($conditionData);

            case ExclusiveFeatureFlagCondition::TYPE:
                return new ExclusiveFeatureFlagCondition($conditionData);

            case VisitorCodeCondition::TYPE:
                return new VisitorCodeCondition($conditionData);

            case PageUrlCondition::TYPE:
                return new PageUrlCondition($conditionData);

            case PageTitleCondition::TYPE:
                return new PageTitleCondition($conditionData);

            case PageViewNumberCondition::TYPE:
                return new PageViewNumberCondition($conditionData);

            case PreviousPageCondition::TYPE:
                return new PreviousPageCondition($conditionData);

            case DeviceCondition::TYPE:
                return new DeviceCondition($conditionData);

            case BrowserCondition::TYPE:
                return new BrowserCondition($conditionData);

            case ConversionCondition::TYPE:
                return new ConversionCondition($conditionData);

            case SdkLanguageCondition::TYPE:
                return new SdkLanguageCondition($conditionData);

            case CookieCondition::TYPE:
                return new CookieCondition($conditionData);

            case GeolocationCondition::TYPE:
                return new GeolocationCondition($conditionData);

            case OperatingSystemCondition::TYPE:
                return new OperatingSystemCondition($conditionData);

            case SegmentCondition::TYPE:
                return new SegmentCondition($conditionData);

            case VisitNumberTotalCondition::TYPE:
                return new VisitNumberTotalCondition($conditionData);

            case VisitNumberTodayCondition::TYPE:
                return new VisitNumberTodayCondition($conditionData);

            case VisitorNewReturnCondition::TYPE:
                return new VisitorNewReturnCondition($conditionData);

            case TimeElapsedSinceVisitCondition::FIRST_VISIT_TYPE:
            case TimeElapsedSinceVisitCondition::LAST_VISIT_TYPE:
                return new TimeElapsedSinceVisitCondition($conditionData);

            case KcsHeatRangeCondition::TYPE:
                return new KcsHeatRangeCondition($conditionData);

            default:
                return new UnknownCondition($conditionData);
        }
    }
}
