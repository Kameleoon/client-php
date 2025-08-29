<?php

declare(strict_types=1);

namespace Kameleoon\Data\Manager;

use Kameleoon\Configuration\Rule;
use Kameleoon\Configuration\VariationByExposition;

/** @internal */
class ForcedFeatureVariation extends ForcedVariation
{
    private string $featureKey;
    private bool $simulated;

    public function __construct(string $featureKey, ?Rule $rule, ?VariationByExposition $varByExp, bool $simulated)
    {
        parent::__construct($rule, $varByExp);
        $this->featureKey = $featureKey;
        $this->simulated = $simulated;
    }

    public function getFeatureKey(): string
    {
        return $this->featureKey;
    }

    public function isSimulated(): bool
    {
        return $this->simulated;
    }

    public function __toString(): string
    {
        return "ForcedFeatureVariation{" .
            "featureKey:'" . $this->featureKey .
            "',rule:" . $this->rule .
            ",varByExp:" . $this->varByExp .
            ",simulated:" . ($this->simulated ? "true" : "false") .
            "}";
    }
}
