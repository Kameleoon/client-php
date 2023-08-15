<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

class DeviceCondition extends TargetingCondition
{
    const TYPE = "DEVICE_TYPE";

    private string $deviceType;

    public function __construct($conditionData)
    {
        parent::__construct($conditionData);
        $this->deviceType = $conditionData->device ?? "";
    }

    public function check($data): bool
    {
        $device = $this->getLastTargetingData($data, "Kameleoon\Data\Device");
        return $device !== null && $device->getType() == $this->deviceType;
    }
}
