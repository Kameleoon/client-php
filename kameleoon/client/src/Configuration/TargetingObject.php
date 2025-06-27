<?php

namespace Kameleoon\Configuration;

use Kameleoon\Targeting\TargetingSegment;
use Kameleoon\Targeting\TargetingTreeBuilder;

class TargetingObject
{
    private $segment;
    private $targetingSegment;

    public function __construct($targetingObject, array $segments)
    {
        $segmentId = $targetingObject->segmentId;
        $this->segment = ($segmentId !== null) ? $segments[$segmentId] ?? null : null;
    }

    public function getTargetingSegment()
    {
        if ($this->segment && !$this->targetingSegment) {
            $targetingTree = TargetingTreeBuilder::createTargetingTree($this->segment->conditionsData);
            $this->targetingSegment = new TargetingSegment($targetingTree);
        }
        return $this->targetingSegment;
    }

    public function getSegment()
    {
        return $this->segment;
    }
}
