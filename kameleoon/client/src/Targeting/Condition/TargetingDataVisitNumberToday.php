<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Data\VisitorVisits;
use Kameleoon\Helpers\TimeHelper;

final class TargetingDataVisitNumberToday
{
    public int $currentVisitTimeStarted;
    public VisitorVisits $visitorVisits;

    public function __construct(?int $currentVisitTimeStarted, ?VisitorVisits $visitorVisits)
    {
        $this->currentVisitTimeStarted = $currentVisitTimeStarted ?? TimeHelper::todayStartInMilliseconds();
        $this->visitorVisits = VisitorVisits::getVisitorVisits($visitorVisits);
    }
}
