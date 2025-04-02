<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

class TargetPersonalizationCondition extends TargetingCondition
{
    const TYPE = "TARGET_PERSONALIZATION";

    private int $personalizationId;

    public function __construct($conditionData)
    {
        parent::__construct($conditionData);
        $this->personalizationId = $conditionData->personalizationId ?? -1;
    }

    public function check($data): bool
    {
        if (!is_array($data)) {
            return false;
        }
        $personalizations = $data;
        return array_key_exists($this->personalizationId, $personalizations);
    }
}
