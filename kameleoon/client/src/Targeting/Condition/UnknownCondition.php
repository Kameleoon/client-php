<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

class UnknownCondition extends TargetingCondition
{
    const TYPE = "UNKNOWN";

    public function check($data): bool
    {
        return true;
    }
}
