<?php

namespace Kameleoon\Configuration;

use Kameleoon\Exception\FeatureVariationNotFound;

class Rule extends TargetingObject
{
    public const EXPERIMENTATION = "EXPERIMENTATION";
    public const TARGETED_DELIVERY = "TARGETED_DELIVERY";

    public $order;
    public $id;
    public $type;
    public $exposition;
    public $experimentId;
    public ?int $respoolTime;
    public $variationByExposition;

    public function __construct($rule)
    {
        parent::__construct($rule);
        $this->id = $rule->id;
        $this->order = $rule->order;
        $this->type = $rule->type;
        $this->exposition = $rule->exposition;
        $this->experimentId = $rule->experimentId;
        $this->respoolTime = $rule->respoolTime;
        $this->variationByExposition = array_map(
            fn ($var) => new VariationByExposition($var),
            $rule->variationByExposition
        );
    }

    public function getVariationIdByKey(string $key): ?int
    {
        foreach ($this->variationByExposition as $varByExp) {
            if ($varByExp->variationKey == $key) {
                return $varByExp;
            }
        }
        return null;
    }

    public function getVariationByKey(string $variationKey): VariationByExposition
    {
        foreach ($this->variationByExposition as $varByExp) {
            if ($varByExp->variationKey == $variationKey) {
                return $varByExp;
            }
        }
        throw new FeatureVariationNotFound("{$this} does not contain variation '{$variationKey}'");
    }

    public function isTargetedDelivery(): bool
    {
        return $this->type === Rule::TARGETED_DELIVERY;
    }
    public function isExperiment(): bool
    {
        return $this->type === Rule::EXPERIMENTATION;
    }

    public function __toString(): string {
        return "Rule{id:$this->id,exposition:$this->exposition}";
    }
}
