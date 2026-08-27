<?php

namespace Kameleoon\Exception;

use Kameleoon\Exception\KameleoonException;
use Throwable;

class FeatureException extends KameleoonException
{
    public function __construct($message, ?Throwable $previous = null)
    {
        parent::__construct("Feature Exception: " . $message, $previous);
    }
}
