<?php

namespace Kameleoon\Targeting;

class TargetingSegment
{
    // null if no targeting condition
    private $targetingTree;

    public function __construct($targetingTree)
    {
        $this->targetingTree = $targetingTree;
    }

    public function getTargetingTree()
    {
        return $this->targetingTree;
    }
}
