<?php

namespace Kameleoon\Targeting;

use Kameleoon\Targeting\TargetingTreeBuilder;

class TargetingSegment
{
    private int $id;
    private bool $audienceTracking;
    private ?TargetingTree $targetingTree; // null if no targeting condition
    private $obj;

    public function __construct($obj)
    {
        $this->obj = $obj;
        $this->id = $obj->id ?? -1;
        $this->audienceTracking = $obj->audienceTracking ?? false;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTargetingTree(): ?TargetingTree
    {
        if (!isset($this->targetingTree)) {
            $this->targetingTree = $this->obj
                ? TargetingTreeBuilder::createTargetingTree($this->obj->conditionsData)
                : null;
        }
        return $this->targetingTree;
    }

    public function getAudienceTracking(): bool
    {
        return $this->audienceTracking;
    }

    public function __toString(): string
    {
        return "TargetingSegment{id:$this->id,audienceTracking:$this->audienceTracking}";
    }
}
