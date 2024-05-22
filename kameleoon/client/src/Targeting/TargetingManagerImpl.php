<?php

declare(strict_types=1);

namespace Kameleoon\Targeting;

use Kameleoon\Configuration\DataFile;
use Kameleoon\Configuration\TargetingObject;
use Kameleoon\Data\Manager\VisitorManager;
use Kameleoon\Helpers\SdkVersion;
use Kameleoon\Targeting\Condition\CookieCondition;
use Kameleoon\Targeting\Condition\CustomDatum;
use Kameleoon\Targeting\Condition\ExclusiveFeatureFlagCondition;
use Kameleoon\Targeting\Condition\GeolocationCondition;
use Kameleoon\Targeting\Condition\OperatingSystemCondition;
use Kameleoon\Targeting\Condition\TargetFeatureFlagCondition;
use Kameleoon\Targeting\Condition\BrowserCondition;
use Kameleoon\Targeting\Condition\ConversionCondition;
use Kameleoon\Targeting\Condition\DeviceCondition;
use Kameleoon\Targeting\Condition\PageTitleCondition;
use Kameleoon\Targeting\Condition\PageUrlCondition;
use Kameleoon\Targeting\Condition\PageViewNumberCondition;
use Kameleoon\Targeting\Condition\PreviousPageCondition;
use Kameleoon\Targeting\Condition\SdkInfo;
use Kameleoon\Targeting\Condition\SdkLanguageCondition;
use Kameleoon\Targeting\Condition\SegmentCondition;
use Kameleoon\Targeting\Condition\TimeElapsedSinceVisitCondition;
use Kameleoon\Targeting\Condition\VisitNumberTodayCondition;
use Kameleoon\Targeting\Condition\VisitNumberTotalCondition;
use Kameleoon\Targeting\Condition\VisitorCodeCondition;
use Kameleoon\Targeting\Condition\VisitorNewReturnCondition;

class TargetingManagerImpl implements TargetingManager
{
    private VisitorManager $visitorManager;
    private DataFile $dataFile;

    public function __construct(VisitorManager $visitorManager)
    {
        $this->visitorManager = $visitorManager;
    }

    public function setDataFile(DataFile $dataFile): void
    {
        $this->dataFile = $dataFile;
    }

    public function checkTargeting(string $visitorCode, int $containerID, TargetingObject $targetingObject): bool
    {
        $targeting = true;

        // performing targeting
        $targetingSegment = $targetingObject->getTargetingSegment();
        if (null != $targetingSegment) {
            $targetingTree = $targetingSegment->getTargetingTree();
            // obtaining targeting checking result and assigning targeting to container
            $targeting = TargetingEngine::checkTargetingTree(
                $targetingTree,
                function (string $type) use ($visitorCode, $containerID) {
                    return $this->getConditionData($type, $visitorCode, $containerID);
                }
            );
        }

        return $targeting;
    }

    private function getConditionData(string $type, string $visitorCode, int $campaignId)
    {
        $visitor = $this->visitorManager->getVisitor($visitorCode);
        switch ($type) {
            case CustomDatum::TYPE:
                return !is_null($visitor) ? $visitor->getCustomData() : null;

            case PageTitleCondition::TYPE:
                return !is_null($visitor) ? $visitor->getPageView() : null;

            case PageUrlCondition::TYPE:
            case PageViewNumberCondition::TYPE:
            case PreviousPageCondition::TYPE:
                return !is_null($visitor) ? $visitor->getPageViewVisit() : null;

            case DeviceCondition::TYPE:
                return !is_null($visitor) ? $visitor->getDevice() : null;

            case BrowserCondition::TYPE:
                return !is_null($visitor) ? $visitor->getBrowser() : null;

            case ConversionCondition::TYPE:
                return !is_null($visitor) ? $visitor->getConversion() : null;

            case VisitorCodeCondition::TYPE:
                return $visitorCode;

            case TargetFeatureFlagCondition::TYPE:
                return [$this->dataFile, ($visitor != null) ? $visitor->getAssignedVariations() : []];

            case ExclusiveFeatureFlagCondition::TYPE:
                return [$campaignId, ($visitor != null) ? $visitor->getAssignedVariations() : []];

            case SdkLanguageCondition::TYPE:
                return new SdkInfo(SdkVersion::getName(), SdkVersion::getVersion());

            case CookieCondition::TYPE:
                return ($visitor != null) ? $visitor->getCookie() : null;

            case GeolocationCondition::TYPE:
                return ($visitor != null) ? $visitor->getGeolocation() : null;

            case OperatingSystemCondition::TYPE:
                return ($visitor != null) ? $visitor->getOperatingSystem() : null;

            case SegmentCondition::TYPE:
                return [
                    $this->dataFile,
                    function (string $type) use ($visitorCode, $campaignId) {
                        return $this->getConditionData($type, $visitorCode, $campaignId);
                    }
                ];

            case TimeElapsedSinceVisitCondition::FIRST_VISIT_TYPE:
            case TimeElapsedSinceVisitCondition::LAST_VISIT_TYPE:
            case VisitNumberTotalCondition::TYPE:
            case VisitNumberTodayCondition::TYPE:
            case VisitorNewReturnCondition::TYPE:
                return ($visitor != null) ? $visitor->getVisitorVisits() : null;

            default:
                return null;
        }
    }
}
