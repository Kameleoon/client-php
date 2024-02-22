<?php

namespace Kameleoon\Helpers;

use Kameleoon\Exception\VisitorCodeInvalid;
use Kameleoon\Helpers\RandomString;

class VisitorCodeManager
{
    public const ALPHABET = "abcdefghijklmnopqrstuvwxyz0123456789";
    public const VISITOR_CODE_LENGTH = 16;
    public const VISITOR_CODE_MAX_LENGTH = 255;

    public static function validateVisitorCode($visitorCode)
    {
        if ($visitorCode === null || empty($visitorCode)) {
            throw new VisitorCodeInvalid("Visitor code is empty");
        }
        if (strlen($visitorCode) > self::VISITOR_CODE_MAX_LENGTH) {
            throw new VisitorCodeInvalid(
                sprintf("Visitor code max length is %d characters", self::VISITOR_CODE_MAX_LENGTH)
            );
        }
    }

    public static function generateVisitorCode()
    {
        return RandomString::generate(self::ALPHABET, self::VISITOR_CODE_LENGTH);
    }
}
