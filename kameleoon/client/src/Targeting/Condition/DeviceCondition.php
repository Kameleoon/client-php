<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Data\Device;

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
        return $data instanceof Device && $data->getType() == $this->deviceType;
    }
}
