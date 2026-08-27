<?php

namespace Kameleoon\Exception;

use Exception;
use Throwable;

class KameleoonException extends Exception
{
    private const KAMELEOON_SDK = "Kameleoon SDK: ";

    public function __construct($message, ?Throwable $previous = null)
    {
        parent::__construct(self::KAMELEOON_SDK . $message, 0, $previous);
    }

    public function __toString()
    {
        return get_class($this) . ": {$this->message}\n";
    }
}
