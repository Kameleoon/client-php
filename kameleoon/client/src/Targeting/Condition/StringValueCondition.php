<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

class StringValueCondition extends TargetingCondition
{
    protected string $conditionValue;
    protected string $operator;

    public function __construct($conditionData, $value)
    {
        parent::__construct($conditionData);
        $this->conditionValue = $value;
        $this->operator = $conditionData->matchType ?? TargetingOperator::UNKNOWN;
    }

    public function check($data): bool
    {
        if (!is_string($data)) {
            return false;
        }
        return $this->checkTargeting($data);
    }

    protected function checkTargeting(?string $value)
    {
        if ($value === null) {
            return false;
        }
        switch ($this->operator) {
            case TargetingOperator::EXACT:
                return $value === $this->conditionValue;

            case TargetingOperator::CONTAINS:
                return strpos($value, $this->conditionValue) !== false;

            case TargetingOperator::REGULAR_EXPRESSION:
                $pattern = '/' . $this->conditionValue . '/';
                return (bool)(preg_match($pattern, $value));

            default:
                error_log("Unexpected comparing operation for " . $this->getType() . " condition: " . $this->operator);
                return false;
        }
    }
}
