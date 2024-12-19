<?php

declare(strict_types=1);

namespace Kameleoon\Configuration;

class RuleInfo
{
    private FeatureFlag $featureFlag;
    private Rule $rule;

    public function __construct(FeatureFlag $featureFlag, Rule $rule)
    {
        $this->featureFlag = $featureFlag;
        $this->rule = $rule;
    }

    public function getFeatureFlag(): FeatureFlag
    {
        return $this->featureFlag;
    }

    public function getRule(): Rule
    {
        return $this->rule;
    }
}
