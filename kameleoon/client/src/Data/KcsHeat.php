<?php

declare(strict_types=1);

namespace Kameleoon\Data;

/** @internal */
class KcsHeat implements BaseData
{
    private array $values;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function __toString(): string
    {
        return "KcsHeat{values:" . json_encode($this->values) . "}";
    }
}
