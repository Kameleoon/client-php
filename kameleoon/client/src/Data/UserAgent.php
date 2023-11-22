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

    public function getQuery(): string
    {
        return "";
    }
}
