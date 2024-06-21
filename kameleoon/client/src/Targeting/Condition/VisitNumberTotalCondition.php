<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Data\VisitorVisits;

class VisitNumberTotalCondition extends NumberCondition
{
    const TYPE = "VISITS";

    public function __construct($conditionData)
    {
        parent::__construct($conditionData, $conditionData->visitCount ?? null);
    }

    public function check($data): bool
    {
        if (!VisitorVisits::isVisitorVisits($data) || ($this->conditionValue === null)) {
            return false;
        }
        $prevVisitsTime = VisitorVisits::getPreviousVisitTimestamps($data);
        return $this->checkTargeting(count($prevVisitsTime) + 1); // +1 for current visit
    }
}
