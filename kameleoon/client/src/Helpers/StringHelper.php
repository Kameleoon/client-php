<?php

namespace Kameleoon\Helpers;

class StringHelper
{
    public static function strval($value): string
    {
        if ($value === true) {
            return 'true';
        }

        if ($value === false) {
            return 'false';
        }

        return strval($value);
    }
}
