<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

class VisitorCodeCondition extends StringValueCondition
{
    const TYPE = "VISITOR_CODE";

    public function __construct($conditionData)
    {
        parent::__construct($conditionData, $conditionData->visitorCode);
    }
}
