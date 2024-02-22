<?php

namespace Kameleoon\Configuration;

class Variation
{
    public const VARIATION_OFF = "off";

    public $key;
    public $variables;

    public function __construct($variation)
    {
        $this->key = $variation->key;
        $this->variables = array_reduce(
            $variation->variables,
            function ($res, $var) {
                $res[$var->key] = new Variable($var);
                return $res;
            },
            []
        );
    }

    public function getVariable(?string $key)
    {
        return $this->variables[$key] ?? null;
    }
}
