<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Data\OperatingSystem;

class OperatingSystemCondition extends TargetingCondition
{
    const TYPE = "OPERATING_SYSTEM";

    private int $osType;

    public function __construct($conditionData)
    {
        parent::__construct($conditionData);
        $this->osType = OperatingSystem::$typeIndices[$conditionData->os ?? ""] ?? -1;
    }

    public function check($data): bool
    {
        return ($data instanceof OperatingSystem) && ($data->getType() == $this->osType);
    }
}
