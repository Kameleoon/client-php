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

    protected function getLastTargetingData($data, $className)
    {
        for ($i = count($data) - 1; $i >= 0; $i--) {
            $data = $data[$i]->getData();
            if (get_class($data) === $className) {
                return $data;
            }
        }
        return null;
    }
}
