<?php

declare(strict_types=1);

namespace Kameleoon\Managers\Evaluation;

use Kameleoon\Configuration\Experiment;
use Kameleoon\Configuration\Rule;
use Kameleoon\Configuration\VariationByExposition;
use Kameleoon\Data\Manager\ForcedExperimentVariation;
use Kameleoon\Data\Manager\ForcedVariation;

class EvaluatedExperiment
{
    private VariationByExposition $varByExp;
    private Experiment $experiment;
    private $ruleType;

    public function __construct(VariationByExposition $varByExp, Experiment $experiment, $ruleType)
    {
        $this->varByExp = $varByExp;
        $this->experiment = $experiment;
        $this->ruleType = $ruleType;
    }

    public static function fromVarByExpRule(VariationByExposition $varByExp, Rule $rule): EvaluatedExperiment
    {
        return new EvaluatedExperiment($varByExp, $rule->experiment, $rule->type);
    }

    public static function fromForcedVariation(ForcedVariation $forcedVariation): ?EvaluatedExperiment
    {
        return (($forcedVariation->getRule() !== null) && ($forcedVariation->getVarByExp() !== null))
            ? self::fromVarByExpRule($forcedVariation->getVarByExp(), $forcedVariation->getRule()) : null;
    }

    public static function fromForcedExperimentVariation(
        ForcedExperimentVariation $forcedVariation): EvaluatedExperiment
    {
        return self::fromVarByExpRule($forcedVariation->getVarByExp(), $forcedVariation->getRule());
    }

    public function getVarByExp(): VariationByExposition
    {
        return $this->varByExp;
    }

    public function getExperiment(): Experiment
    {
        return $this->experiment;
    }

    public function getRuleType()
    {
        return $this->ruleType;
    }

    public function __toString(): string
    {
        return "EvaluatedExperiment{varByExp:$this->varByExp,experiment:$this->experiment,ruleType:$this->ruleType}";
    }
}
