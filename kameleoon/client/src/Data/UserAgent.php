<?php

namespace Kameleoon\Data;

use Kameleoon\Data\Data;

class UserAgent implements Data
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /** @internal */
    public function getQuery(): string
    {
        return "";
    }

    public function __toString(): string
    {
        return "UserAgent{value:'" . $this->value . "'}";
    }
}
