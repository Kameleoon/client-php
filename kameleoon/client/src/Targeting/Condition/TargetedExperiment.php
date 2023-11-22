<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

class TargetedExperiment extends TargetingCondition
{
    const TYPE = "TARGET_EXPERIMENT";

    private $experiment;

    private $variation;

    private $operator;

    public function getExperiment()
    {
        return $this->experiment;
    }

    public function getOperator()
    {
        return $this->operator;
    }

    public function getVariation()
    {
        return $this->variation;
    }

    public function __construct($conditionData)
    {
        parent::__construct($conditionData);
        $this->experiment = $conditionData->experiment ?? null;
        $this->variation = $conditionData->variation ?? null;
        $this->operator = $conditionData->variationMatchType ?? TargetingOperator::UNKNOWN;
    }

    public function check($assignedVariations): bool
    {
        $targeting = false;
        $variation = $assignedVariations[$this->experiment] ?? null;
        $currentExperimentIdExist = isset($variation);
        switch ($this->operator) {
            case TargetingOperator::EXACT:
                $targeting = $currentExperimentIdExist && $variation->getVariationId() === $this->variation;
                break;
            case TargetingOperator::ANY:
                $targeting = $currentExperimentIdExist;
                break;
            default:
                break;
        }
        return $targeting;
    }
}
