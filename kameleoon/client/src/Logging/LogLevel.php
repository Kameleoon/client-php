<?php

declare(strict_types=1);

namespace Kameleoon\Logging;

class LogLevel
{
    const NONE = 0;
    const ERROR = 1;
    const WARNING = 2;
    const INFO = 3;
    const DEBUG = 4;

    public static function getLevelName($level): string
    {
        switch ($level) {
            case self::NONE:
                return 'NONE';
            case self::ERROR:
                return 'ERROR';
            case self::WARNING:
                return 'WARNING';
            case self::INFO:
                return 'INFO';
            case self::DEBUG:
                return 'DEBUG';
            default:
                return 'UNKNOWN';
        }
    }
}
