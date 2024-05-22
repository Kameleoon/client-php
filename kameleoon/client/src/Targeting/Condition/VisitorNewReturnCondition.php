<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Data\VisitorVisits;

class VisitorNewReturnCondition extends TargetingCondition
{
    const TYPE = "NEW_VISITORS";
    const VISITOR_TYPE_NEW = "NEW", VISITOR_TYPE_RETURN = "RETURNING";

    private ?string $visitorType;

    public function __construct($conditionData)
    {
        parent::__construct($conditionData);
        $this->visitorType = $conditionData->visitorType ?? null;
    }

    public function check($data): bool
    {
        if (($data instanceof VisitorVisits) && ($this->visitorType !== null)) {
            $prevVisitsTime = $data->getPreviousVisitTimestamps();
            switch ($this->visitorType) {
                case self::VISITOR_TYPE_NEW:
                    return empty($prevVisitsTime);
                case self::VISITOR_TYPE_RETURN:
                    return !empty($prevVisitsTime);
            }
        }
        return false;
    }
}
