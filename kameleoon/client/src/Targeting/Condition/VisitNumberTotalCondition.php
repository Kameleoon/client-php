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
        $visitorVisits = null;
        if (!VisitorVisits::tryGetVisitorVisits($data, $visitorVisits) || ($this->conditionValue === null)) {
            return false;
        }
        return $this->checkTargeting(count($visitorVisits->getPrevVisits()) + 1); // +1 for current visit
    }
}
