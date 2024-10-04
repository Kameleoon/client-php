<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Logging\KameleoonLogger;

class NumberCondition extends TargetingCondition
{
    protected $conditionValue;
    protected string $operator;

    public function __construct($conditionData, $value)
    {
        parent::__construct($conditionData);
        $this->conditionValue = $value;
        $this->operator = $conditionData->matchType ?? TargetingOperator::UNKNOWN;
    }

    public function check($data): bool
    {
        return $this->checkTargeting($data);
    }

    protected function checkTargeting($value)
    {
        if (!(is_int($value) || is_float($value))) {
            return false;
        }
        switch ($this->operator) {
            case TargetingOperator::EQUAL:
                return $value == $this->conditionValue;

            case TargetingOperator::GREATER:
                return $value > $this->conditionValue;

            case TargetingOperator::LOWER:
                return $value < $this->conditionValue;

            default:
                KameleoonLogger::error("Unexpected comparing operation for '%s' condition: '%s'",
                    $this->getType(), $this->operator);
                return false;
        }
    }
}
