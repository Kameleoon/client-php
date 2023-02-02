<?php

namespace Kameleoon\Configuration;

use Kameleoon\Targeting\TargetingSegment;
use Kameleoon\Targeting\TargetingTreeBuilder;

class TargetingObject
{
    private $segment;
    private $targetingSegment;

    public function __construct($targetingObject)
    {
        $this->segment = $targetingObject->segment;
    }

    public function getTargetingSegment() {
        if ($this->segment && !$this->targetingSegment) {
            $this->targetingSegment = new TargetingSegment();
            $targetingTreeBuilder = new TargetingTreeBuilder();
            $targetingTree = $targetingTreeBuilder->createTargetingTree($this->segment->conditionsData);
            $this->targetingSegment->setTargetingTree($targetingTree);
        }
        return $this->targetingSegment;
    }
}
?>
