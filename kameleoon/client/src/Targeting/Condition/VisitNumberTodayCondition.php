<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Data\VisitorVisits;
use Kameleoon\Helpers\TimeHelper;

class VisitNumberTodayCondition extends NumberCondition
{
    const TYPE = "SAME_DAY_VISITS";

    public function __construct($conditionData)
    {
        parent::__construct($conditionData, $conditionData->visitCount ?? null);
    }

    public function check($data): bool
    {
        if (!($data instanceof TargetingDataVisitNumberToday) || ($this->conditionValue === null)) {
            return false;
        }
        $startOfDay = TimeHelper::todayStartInMilliseconds();
        $prevVisits = $data->visitorVisits->getPrevVisits();
        $todayVisitNumber = 0;
        while (($todayVisitNumber < count($prevVisits))
            && ($prevVisits[$todayVisitNumber]->getTimeStarted() >= $startOfDay)) {
            $todayVisitNumber++;
        }
        if ($data->currentVisitTimeStarted >= $startOfDay) {
            $todayVisitNumber++;
        }
        return $this->checkTargeting($todayVisitNumber);
    }
}
