<?php

namespace Kameleoon\Exception;

use Kameleoon\Exception\KameleoonException;
use Throwable;

class ConfigException extends KameleoonException
{
    public function __construct($message, ?Throwable $previous = null)
    {
        parent::__construct("Config Invalid: " . $message, $previous);
    }
}
