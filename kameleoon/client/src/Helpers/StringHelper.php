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

    private static string $hidCh = '*';
    private static int $visCount = 4;

    public static function objectToString($data): string
    {
        if ($data === null) {
            return "null";
        }

        if (is_array($data)) {
            $value = "[";
            $elements = array_map(function($key, $val) {
                $keyString = null;
                if (!is_int($key)) {
                    $keyString = self::objectToString($key);
                }
                if (is_string($val)) {
                    $valString = "'" . $val . "'";
                } else {
                    $valString = self::objectToString($val);
                }
                return $keyString === null ? $valString : "$keyString:$valString";
            }, array_keys($data), $data);
            $value .= implode(",", $elements);
            $value .= "]";
            return $value;
        }

        if (is_bool($data)) {
            return $data ? 'true' : 'false';
        }

        if (is_object($data)) {
            if (method_exists($data, '__toString')) {
                return (string) $data;
            } else {
                return json_encode($data);
            }
        }

        return (string) $data;
    }

    public static function prepareArgs(...$args): array {
        return array_map([self::class, 'objectToString'], $args);
    }

    public static function secret($secret): string
    {
        if ($secret === null) {
            return "null";
        }

        $length = strlen($secret);

        if ($length <= self::$visCount) {
            return str_repeat(self::$hidCh, $length);
        }

        $hiddenLength = max($length - self::$visCount, self::$visCount);

        return substr($secret, 0, $length - $hiddenLength) . str_repeat(self::$hidCh, $hiddenLength);
    }
}
