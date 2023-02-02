<?php

namespace Kameleoon\Configuration;

class Rule extends TargetingObject
{
    public const EXPERIMENTATION = "EXPERIMENTATION";
    public const TARGETED_DELIVERY = "TARGETED_DELIVERY";

    public $type;
    public $exposition;
    public $experimentId;
    private $variationByExposition;
    private $targetingSegment;

    public function __construct($rule)
    {
        parent::__construct($rule);
        $this->type = $rule->type;
        $this->exposition = $rule->exposition;
        $this->experimentId = $rule->experimentId;
        $this->variationByExposition = array_map(
            fn($var) => new VariationByExposition($var),
            $rule->variationByExposition
        );
    }

    public function getVariationKey(float $hashDouble): ?string {
        $total = 0.0;
        foreach ($this->variationByExposition as $variationByExposition) {
            $total += $variationByExposition->exposition;
            if ($total >= $hashDouble) {
                return $variationByExposition->variationKey;
            }
        }
        return null;
    }

    public function getVariationIdByKey(string $key): ?int {
        $arrayVariation = array_filter($this->variationByExposition, function ($v, $k) use($key) {
            return $v->variationKey == $key;
        }, ARRAY_FILTER_USE_BOTH);
        $variation = array_pop($arrayVariation);
        return !is_null($variation) ? $variation->variationId : null;
    }

}
?>
