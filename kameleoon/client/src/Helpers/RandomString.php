<?php

declare(strict_types=1);

namespace Kameleoon\Helpers;

class RandomString
{
    const HEXADECIMAL_ALPHABET = "0123456789ABCDEF";
    const NONCE_BYTE_LENGTH = 16;
    const VISITOR_CODE_LENGTH = 16;
    const ALPHABET = "abcdefghijklmnopqrstuvwxyz0123456789";

    public static function obtainNonce(): string
    {
        $hexLength = strlen(self::HEXADECIMAL_ALPHABET);
        $result = "";
        for ($i = 0; $i < self::NONCE_BYTE_LENGTH; $i++) {
            $randomNumber = mt_rand(0, $hexLength - 1);
            $result .= self::HEXADECIMAL_ALPHABET[$randomNumber];
        }

        return $result;
    }

    public static function generateVisitorCode()
    {
        $alphabetLength = strlen(self::ALPHABET);
        $randomVisitorCode = '';
        for ($i = 0; $i < self::VISITOR_CODE_LENGTH; ++$i) {
            $randomNumber = mt_rand(0, $alphabetLength - 1);
            $randomVisitorCode .= self::ALPHABET[$randomNumber];
        }
        return $randomVisitorCode;
    }
}
