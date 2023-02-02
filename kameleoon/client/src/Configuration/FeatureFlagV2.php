<?php

namespace Kameleoon\Configuration;

class FeatureFlagV2
{
    public $id;
    public $featureKey;
    public $defaultVariationKey;
    private $variations;
    public $rules;

    public function __construct($ff)
    {
        $this->id = $ff->id;
        $this->featureKey = $ff->featureKey;
        $this->defaultVariationKey = $ff->defaultVariationKey;
        $this->variations = array_map(fn($var) => new Variation($var), $ff->variations);
        $this->rules = array_map(fn($rule) => new Rule($rule), $ff->rules);
    }

    public function getVariation(string $key): ?Variation {
        $arrayVariation = array_filter($this->variations, function ($v, $k) use($key) {
            return $v->key == $key;
        }, ARRAY_FILTER_USE_BOTH);
        return array_pop($arrayVariation);
    }
}
?>
