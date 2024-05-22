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
        if (!($data instanceof VisitorVisits) || ($this->conditionValue === null)) {
            return false;
        }
        $prevVisitsTime = $data->getPreviousVisitTimestamps();
        return $this->checkTargeting(count($prevVisitsTime) + 1); // +1 for current visit
    }
}
