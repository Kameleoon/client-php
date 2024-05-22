<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Data\VisitorVisits;
use Kameleoon\Helpers\TimeHelper;

class TimeElapsedSinceVisitCondition extends NumberCondition
{
    const FIRST_VISIT_TYPE = "FIRST_VISIT";
    const LAST_VISIT_TYPE = "LAST_VISIT";

    private bool $isFirstVisit;

    public function __construct($conditionData)
    {
        parent::__construct($conditionData, $conditionData->countInMillis ?? null);
        $this->isFirstVisit = $this->getType() == self::FIRST_VISIT_TYPE;
    }

    public function check($data): bool
    {
        if (($data instanceof VisitorVisits) && ($this->conditionValue !== null)) {
            $prevVisitsTime = $data->getPreviousVisitTimestamps();
            if (empty($prevVisitsTime)) {
                return false;
            }
            $now = TimeHelper::nowInMilliseconds();
            $visitIndex = $this->isFirstVisit ? count($prevVisitsTime) - 1 : 0;
            $visitTimestamp = $prevVisitsTime[$visitIndex];
            return $this->checkTargeting($now - $visitTimestamp);
        }
        return false;
    }
}
