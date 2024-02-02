<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

abstract class TargetingCondition
{
    const NON_EXISTENT_IDENTIFIER = -1;

    private string $type;

    private bool $include;

    abstract public function check($data): bool;

    public function __construct($conditionData)
    {
        $this->type = $conditionData->targetingType ?? "";
        $this->include = $conditionData->isInclude ?? true;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getInclude(): bool
    {
        return $this->include;
    }

    protected function getLastTargetingData($targetingData, $className)
    {
        for ($i = count($targetingData) - 1; $i >= 0; $i--) {
            $data = $targetingData[$i]->getData();
            if (get_class($data) === $className) {
                return $data;
            }
        }
        return null;
    }
}

class TargetingOperator
{
    public const UNDEFINED = "UNDEFINED";
    public const CONTAINS = "CONTAINS";
    public const EXACT = "EXACT";
    public const REGULAR_EXPRESSION = "REGULAR_EXPRESSION";
    public const LOWER = "LOWER";
    public const EQUAL = "EQUAL";
    public const GREATER = "GREATER";
    public const IS_TRUE = "TRUE";
    public const IS_FALSE = "FALSE";
    public const AMONG_VALUES = "AMONG_VALUES";
    public const ANY = "ANY";
    public const UNKNOWN = "UNKNOWN";
}
