<?php

namespace Kameleoon\Configuration;

class Rule extends TargetingObject
{
    private const EXPERIMENTATION = "EXPERIMENTATION";
    private const TARGETED_DELIVERY = "TARGETED_DELIVERY";

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
        $arrayVariation = array_filter($this->variationByExposition, function ($v, $k) use ($key) {
            return $v->variationKey == $key;
        }, ARRAY_FILTER_USE_BOTH);
        $variation = array_pop($arrayVariation);
        return !is_null($variation) ? $variation->variationId : null;
    }

    public function isTargetedDelivery(): bool
    {
        return $this->type === Rule::TARGETED_DELIVERY;
    }
    public function isExperiment(): bool
    {
        return $this->type === Rule::EXPERIMENTATION;
    }
}
