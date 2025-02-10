<?php

namespace Kameleoon\Configuration;

class Rule extends TargetingObject
{
    public const EXPERIMENTATION = "EXPERIMENTATION";
    public const TARGETED_DELIVERY = "TARGETED_DELIVERY";

    public $order;
    public $id;
    public $type;
    public Experiment $experiment;
    public $exposition;
    public ?int $respoolTime;

    public function __construct($rule)
    {
        parent::__construct($rule);
        $this->id = $rule->id;
        $this->order = $rule->order;
        $this->type = $rule->type;
        $this->experiment = new Experiment($rule);
        $this->exposition = $rule->exposition;
        $this->respoolTime = $rule->respoolTime;
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
