<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

class TargetingOperator
{
    public const UNDEFINED = "UNDEFINED";
    public const CONTAINS = "CONTAINS";
    public const EXACT = "EXACT";
    public const REGULAR_EXPRESSION = "REGULAR_EXPRESSION";
    public const LOWER = "LOWER";
    public const EQUAL = "EQUAL";
    public const GREATER = "GREATER";
    public const IS_TRUE = "TRUE";
    public const IS_FALSE = "FALSE";
    public const AMONG_VALUES = "AMONG_VALUES";
    public const ANY = "ANY";
    public const UNKNOWN = "UNKNOWN";
}
