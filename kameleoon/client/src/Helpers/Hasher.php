<?php

namespace Kameleoon\Helpers;

use Kameleoon\Logging\KameleoonLogger;

final class Hasher
{
    const _2_POW_32 = 0x10000_0000;

    public static function obtain(string $visitorCode, int $containerID, ?int $respoolTime = null): float
    {
        $stringToDigest = $visitorCode;
        $stringToDigest .= $containerID;
        if ($respoolTime !== null) {
            $stringToDigest .= $respoolTime;
        }
        return self::calculate($stringToDigest);
    }

    public static function obtainHashForMEGroup(string $visitorCode, string $meGroupName): float
    {
        return self::calculate($visitorCode . $meGroupName);
    }

    public static function calculate(string $stringToDigest): float
    {
        $hashSubstring = substr(hash("sha256", $stringToDigest), 0, 8);
        return hexdec($hashSubstring) / self::_2_POW_32;
    }
}
