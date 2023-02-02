<?php
namespace Kameleoon\Data;

class UserAgent
{
    private string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getValue() {
        return $this->value;
    }
}
