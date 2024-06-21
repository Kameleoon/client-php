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
        if (!VisitorVisits::isVisitorVisits($data) || ($this->conditionValue === null)) {
            return false;
        }
        $startOfDay = TimeHelper::todayStartInMilliseconds();
        $prevVisitsTime = VisitorVisits::getPreviousVisitTimestamps($data);
        $todayVisitNumber = 0;
        while (($todayVisitNumber < count($prevVisitsTime)) && ($prevVisitsTime[$todayVisitNumber] >= $startOfDay)) {
            $todayVisitNumber++;
        }
        return $this->checkTargeting($todayVisitNumber + 1); // +1 for current visit
    }
}
