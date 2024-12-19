<?php

declare(strict_types=1);

namespace Kameleoon\Data\Manager;

use Kameleoon\Configuration\Rule;
use Kameleoon\Configuration\VariationByExposition;
use Kameleoon\Data\BaseData;

abstract class ForcedVariation implements BaseData
{
    protected ?Rule $rule;
    protected ?VariationByExposition $varByExp;

    public function __construct(?Rule $rule, ?VariationByExposition $varByExp)
    {
        $this->rule = $rule;
        $this->varByExp = $varByExp;
    }

    public function getRule(): ?Rule
    {
        return $this->rule;
    }

    public function getVarByExp(): ?VariationByExposition
    {
        return $this->varByExp;
    }
}
