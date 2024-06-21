<?php

declare(strict_types=1);

namespace Kameleoon\Data;

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
}
