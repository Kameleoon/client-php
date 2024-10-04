<?php

declare(strict_types=1);

namespace Kameleoon\Targeting;

use Kameleoon\Configuration\TargetingObject;
use Kameleoon\Data\Manager\VisitorManager;
use Kameleoon\Helpers\SdkVersion;
use Kameleoon\Logging\KameleoonLogger;
use Kameleoon\Managers\Data\DataManager;
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
use Kameleoon\Targeting\Condition\KcsHeatRangeCondition;

class TargetingManagerImpl implements TargetingManager
{
    private VisitorManager $visitorManager;
    private DataManager $dataManager;

    public function __construct(DataManager $dataManager, VisitorManager $visitorManager)
    {
        $this->dataManager = $dataManager;
        $this->visitorManager = $visitorManager;
    }

    public function checkTargeting(string $visitorCode, int $containerID, TargetingObject $targetingObject): bool
    {
        KameleoonLogger::debug("CALL: TargetingManager.checkTargeting(visitorCode: '%s', containerID: %s, rule: %s)",
            $visitorCode, $containerID, $targetingObject);
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
        if ($targeting) {
            KameleoonLogger::info("Visitor '%s' has been targeted for %s", $visitorCode, $targetingObject);
        }
        KameleoonLogger::debug(
            "RETURN: TargetingManager.checkTargeting(visitorCode: '%s', containerID: %s, rule: %s) -> (targeted: %s)",
            $visitorCode, $containerID, $targetingObject, $targeting);
        return $targeting;
    }

    private function getConditionData(string $type, string $visitorCode, int $campaignId)
    {
        KameleoonLogger::debug(
            "CALL: TargetingManager.getConditionData(type: '%s', visitorCode: '%s', campaignId: %s)",
            $type, $visitorCode, $campaignId);
        $visitor = $this->visitorManager->getVisitor($visitorCode);
        switch ($type) {
            case CustomDatum::TYPE:
                $conditionData = !is_null($visitor) ? $visitor->getCustomData() : null;
                break;
            case PageTitleCondition::TYPE:
                $conditionData = !is_null($visitor) ? $visitor->getPageViews() : null;
                break;
            case PageUrlCondition::TYPE:
            case PageViewNumberCondition::TYPE:
            case PreviousPageCondition::TYPE:
                $conditionData = !is_null($visitor) ? $visitor->getPageViewVisits() : null;
                break;
            case DeviceCondition::TYPE:
                $conditionData = !is_null($visitor) ? $visitor->getDevice() : null;
                break;
            case BrowserCondition::TYPE:
                $conditionData = !is_null($visitor) ? $visitor->getBrowser() : null;
                break;
            case ConversionCondition::TYPE:
                $conditionData = !is_null($visitor) ? $visitor->getConversions() : null;
                break;
            case VisitorCodeCondition::TYPE:
                $conditionData = $visitorCode;
                break;
            case TargetFeatureFlagCondition::TYPE:
                $conditionData = [
                    $this->dataManager->getDataFile(),
                    ($visitor != null) ? $visitor->getAssignedVariations() : []
                ];
                break;
            case ExclusiveFeatureFlagCondition::TYPE:
                $conditionData = [$campaignId, ($visitor != null) ? $visitor->getAssignedVariations() : []];
                break;
            case SdkLanguageCondition::TYPE:
                $conditionData = new SdkInfo(SdkVersion::getName(), SdkVersion::getVersion());
                break;
            case CookieCondition::TYPE:
                $conditionData = ($visitor != null) ? $visitor->getCookie() : null;
                break;
            case GeolocationCondition::TYPE:
                $conditionData = ($visitor != null) ? $visitor->getGeolocation() : null;
                break;
            case OperatingSystemCondition::TYPE:
                $conditionData = ($visitor != null) ? $visitor->getOperatingSystem() : null;
                break;
            case SegmentCondition::TYPE:
                $conditionData = [
                    $this->dataManager->getDataFile(),
                    function (string $type) use ($visitorCode, $campaignId) {
                        return $this->getConditionData($type, $visitorCode, $campaignId);
                    }
                ];
                break;
            case TimeElapsedSinceVisitCondition::FIRST_VISIT_TYPE:
            case TimeElapsedSinceVisitCondition::LAST_VISIT_TYPE:
            case VisitNumberTotalCondition::TYPE:
            case VisitNumberTodayCondition::TYPE:
            case VisitorNewReturnCondition::TYPE:
                $conditionData = ($visitor != null) ? $visitor->getVisitorVisits() : null;
                break;
            case KcsHeatRangeCondition::TYPE:
                $conditionData = ($visitor != null) ? $visitor->getKcsHeat() : null;
                break;
            default:
                $conditionData = null;
                break;
        }

        KameleoonLogger::debug(
            "CALL: TargetingManager.getConditionData(type: '%s', visitorCode: '%s', campaignId: %s) -> (conditionData: %s)",
            $type, $visitorCode, $campaignId, $conditionData);
        return $conditionData;
    }
}
