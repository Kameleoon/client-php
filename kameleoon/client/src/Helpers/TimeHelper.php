<?php

declare(strict_types=1);

namespace Kameleoon\Helpers;

use DateTime;

class TimeHelper
{
    public static function nowInMilliseconds(): int
    {
        return (int)(microtime(true) * 1000);
    }

    public static function todayStartInMilliseconds(): int
    {
        return (new DateTime())->setTime(0, 0)->getTimestamp() * 1000;
    }
}
