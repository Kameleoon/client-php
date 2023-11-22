<?php

namespace Kameleoon\Configuration;

class FeatureFlag
{
    public int $id;
    public string $featureKey;
    private bool $environmentEnabled;
    public string $defaultVariationKey;
    private array $variations;
    public array $rules;

    public function __construct($ff)
    {
        $this->id = $ff->id ?? 0;
        $this->featureKey = $ff->featureKey ?? '';
        $this->environmentEnabled = $ff->environmentEnabled ?? false;
        $this->defaultVariationKey = $ff->defaultVariationKey ?? '';
        $this->variations = array_map(fn ($var) => new Variation($var), $ff->variations);
        $this->rules = array_map(fn ($rule) => new Rule($rule), $ff->rules);
    }

    public function getEnvironmentEnabled(): bool
    {
        return $this->environmentEnabled;
    }

    public function getVariation(string $key): ?Variation
    {
        $arrayVariation = array_filter($this->variations, function ($v) use ($key) {
            return $v->key == $key;
        }, ARRAY_FILTER_USE_BOTH);
        return array_pop($arrayVariation);
    }
}
