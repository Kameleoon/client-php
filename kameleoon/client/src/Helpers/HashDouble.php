<?php

namespace Kameleoon\Helpers;

class HashDouble
{
    public static function obtain(string $visitorCode, int $containerID, ?int $respoolTime = null)
    {
        $suffix = !is_null($respoolTime) ? (string) $respoolTime : '';
        return floatval(intval(substr(hash("sha256", $visitorCode . $containerID . $suffix), 0, 8), 16) / pow(2, 32));
    }
}
