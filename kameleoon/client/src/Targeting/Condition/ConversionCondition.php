<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

class ConversionCondition extends TargetingCondition
{
    const TYPE = "CONVERSIONS";

    private int $goalId;

    public function __construct($conditionData)
    {
        parent::__construct($conditionData);
        $this->goalId = $conditionData->goalId ?? TargetingCondition::NON_EXISTENT_IDENTIFIER;
    }

    public function check($data): bool
    {
        if (is_iterable($data)) {
            foreach ($data as $conversion) {
                if (
                    $this->goalId === TargetingCondition::NON_EXISTENT_IDENTIFIER
                    || $this->goalId === $conversion->getGoalId()
                ) {
                    return true;
                }
            }
        }
        return false;
    }
}
