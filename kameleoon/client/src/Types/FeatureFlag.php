<?php

declare(strict_types=1);

namespace Kameleoon\Types;

use Kameleoon\Helpers\StringHelper;

class FeatureFlag
{
    /**
     * @var array<string, Variation>
     */
    public array $variations;

    /**
     * @var bool
     */
    public bool $isEnvironmentEnabled;

    /**
     * @var array<Rule>
     */
    public array $rules;

    /**
     * @var string
     */
    public string $defaultVariationKey;

    /**
     * @internal
     * @param array<string, Variation> $variations
     * @param bool $isEnvironmentEnabled
     * @param array<Rule> $rules
     * @param string $defaultVariationKey
     */
    public function __construct(
        array $variations,
        bool $isEnvironmentEnabled,
        array $rules,
        string $defaultVariationKey
    ) {
        $this->variations = $variations;
        $this->isEnvironmentEnabled = $isEnvironmentEnabled;
        $this->rules = $rules;
        $this->defaultVariationKey = $defaultVariationKey;
    }

    public function getDefaultVariation(): ?Variation
    {
        return $this->variations[$this->defaultVariationKey] ?? null;
    }

    public function __toString(): string
    {
        $isEnvironmentEnabled = StringHelper::sbool($this->isEnvironmentEnabled);
        $variations = StringHelper::sarray($this->variations);
        $rules = StringHelper::sarray($this->rules);
        return "FeatureFlag{variations:$variations,isEnvironmentEnabled:$isEnvironmentEnabled,rules:$rules,"
            . "defaultVariationKey:'$this->defaultVariationKey'}";
    }
}
