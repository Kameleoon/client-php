<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Logging\KameleoonLogger;

class TargetExperimentCondition extends TargetingCondition
{
    const TYPE = "TARGET_EXPERIMENT";

    private int $variationId;
    private int $experimentId;
    private string $variationMatchType;

    public function __construct($conditionData)
    {
        parent::__construct($conditionData);
        $this->variationId = $conditionData->variationId ?? -1;
        $this->experimentId = $conditionData->experimentId ?? -1;
        $this->variationMatchType = $conditionData->variationMatchType ?? TargetingOperator::UNKNOWN;
    }

    public function check($data): bool
    {
        if (!is_array($data)) {
            return false;
        }
        $variations = $data;
        $variation = $variations[$this->experimentId] ?? null;
        switch ($this->variationMatchType) {
            case TargetingOperator::ANY:
                return $variation !== null;
            case TargetingOperator::EXACT:
                return ($variation !== null) && ($variation->getVariationId() == $this->variationId);
        }
        KameleoonLogger::error(
            "Unexpected variation match type for 'TargetExperimentCondition' condition: '%s'", $this->variationMatchType
        );
        return false;
    }
}
