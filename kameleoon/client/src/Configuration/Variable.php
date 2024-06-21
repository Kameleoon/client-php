<?php

namespace Kameleoon\Configuration;

class Variable
{
    private const JSON_TYPE = "JSON";

    public $key;
    public $type;
    private $value;

    public function __construct($variable)
    {
        $this->key = $variable->key;
        $this->type = $variable->type;
        $this->value = $variable->value;
    }

    public function getValue()
    {
        if ($this->type === self::JSON_TYPE) {
            return json_decode($this->value);
        } else {
            return $this->value;
        }
    }
}
