<?php
namespace Kameleoon\Targeting;

class TargetingSegment
{
    // null if no targeting condition
    private $targetingTree;

    public function getTargetingTree()
    {
        return $this->targetingTree;
    }

    public function setTargetingTree($targetingTree)
    {
        $this->targetingTree = $targetingTree;
    }
}
