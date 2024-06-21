<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Data\KcsHeat;

class KcsHeatRangeCondition extends TargetingCondition
{
    const TYPE = "HEAT_SLICE";

    private int $goalId;
    private int $keyMomentId;
    private float $lowerBound, $upperBound;

    public function __construct($conditionData)
    {
        parent::__construct($conditionData);
        $this->goalId = $conditionData->goalId ?? -1;
        $this->keyMomentId = $conditionData->keyMomentId ?? -1;
        $this->lowerBound = $conditionData->lowerBound ?? PHP_INT_MAX;
        $this->upperBound = $conditionData->upperBound ?? PHP_INT_MIN;
    }

    public function check($data): bool
    {
        if (!($data instanceof KcsHeat)) {
            return false;
        }
        $goalScores = $data->getValues()[$this->keyMomentId] ?? null;
        if (!is_array($goalScores)) {
            return false;
        }
        $score = $goalScores[$this->goalId] ?? null;
        return (is_float($score) || is_int($score)) && ($score >= $this->lowerBound) && ($score <= $this->upperBound);
    }
}
