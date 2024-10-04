<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

abstract class TargetingCondition
{
    const NON_EXISTENT_IDENTIFIER = -1;

    private string $type;

    private bool $include;

    private int $id;

    abstract public function check($data): bool;

    public function __construct($conditionData)
    {
        $this->id = $conditionData->id ?? self::NON_EXISTENT_IDENTIFIER;
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

    public function getId(): int
    {
        return $this->id;
    }
}
