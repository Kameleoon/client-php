<?php

namespace Kameleoon\Data;

class UniqueIdentifier implements Data
{
    private bool $value;

    public function __construct(bool $value)
    {
        $this->value = $value;
    }

    public function getValue(): bool
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return "UniqueIdentifier{value:" . ($this->value ? "true" : "false") . "}";
    }
}
