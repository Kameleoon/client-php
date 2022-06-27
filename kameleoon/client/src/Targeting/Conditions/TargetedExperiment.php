<?php
namespace Kameleoon\Targeting\Conditions;

use Kameleoon\Targeting\TargetingCondition;

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

    public function setExperiment($experiment)
    {
        $this->experiment = $experiment;
    }

    public function getOperator()
    {
        return $this->operator;
    }

    public function setOperator($operator)
    {
        $this->operator = $operator;
    }

    public function getVariation()
    {
        return $this->variation;
    }

    public function setVariation($variation)
    {
        $this->variation = $variation;
    }

    public function check($variationStorage)
    {
        $targeting = false;
        $currentExperimentIdExist = isset($variationStorage[$this->experiment]);
        switch($this->operator) {
            case "EXACT":
                $targeting = $currentExperimentIdExist && $variationStorage[$this->experiment] === $this->variation;
                break;
            case "ANY":
                $targeting = $currentExperimentIdExist;
                break;
            default:
                break;
        }
        return $targeting;
    }
}
