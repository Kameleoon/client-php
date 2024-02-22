<?php

declare(strict_types=1);

namespace Kameleoon\Helpers;

class RandomString
{
    const HEXADECIMAL_ALPHABET = "0123456789ABCDEF";
    const NONCE_BYTE_LENGTH = 16;

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

    public static function generate(string $charset, int $length): string
    {
        $charsetLength = strlen($charset);
        $randomString = '';
        for ($i = 0; $i < $length; ++$i) {
            $randomNumber = mt_rand(0, $charsetLength - 1);
            $randomString .= $charset[$randomNumber];
        }
        return $randomString;
    }
}
