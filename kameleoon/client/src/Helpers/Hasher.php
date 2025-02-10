<?php

namespace Kameleoon\Helpers;

use Kameleoon\Logging\KameleoonLogger;

final class Hasher
{
    const _2_POW_32 = 0x10000_0000;

    public static function obtain(string $visitorCode, ?int $containerID, ?int $respoolTime = null)
    {
        $stringToDigest = $visitorCode;
        if ($containerID !== null) {
            $stringToDigest .= $containerID;
        }
        if ($respoolTime !== null) {
            $stringToDigest .= $respoolTime;
        }
        return self::calculate($stringToDigest);
    }

    public static function calculate(string $stringToDigest): float
    {
        $hashSubstring = substr(hash("sha256", $stringToDigest), 0, 8);
        return hexdec($hashSubstring) / self::_2_POW_32;
    }
}
