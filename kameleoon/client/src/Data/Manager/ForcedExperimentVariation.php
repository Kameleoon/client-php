<?php

declare(strict_types=1);

namespace Kameleoon\Data\Manager;

use Kameleoon\Configuration\Rule;
use Kameleoon\Configuration\VariationByExposition;

/** @internal */
class ForcedExperimentVariation extends ForcedVariation
{
    private bool $forceTargeting;

    public function __construct(Rule $rule, VariationByExposition $varByExp, bool $forceTargeting)
    {
        parent::__construct($rule, $varByExp);
        $this->forceTargeting = $forceTargeting;
    }

    public function getRule(): Rule
    {
        return $this->rule;
    }

    public function getVarByExp(): VariationByExposition
    {
        return $this->varByExp;
    }

    public function isForceTargeting(): bool
    {
        return $this->forceTargeting;
    }

    public function __toString(): string
    {
        return "ForcedExperimentVariation{" .
            "rule:" . $this->rule .
            ",varByExp:" . $this->varByExp .
            ",forceTargeting:" . ($this->forceTargeting ? "true" : "false") .
            "}";
    }
}
