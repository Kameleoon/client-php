<?php
namespace Kameleoon\Targeting;

class TargetingTree
{
    // true = OR operation, false = AND operation, ignored if targetingCondition is null and ought to be null if targetingCondition is not null
    private $orOperator;

    // ignored if targetingCondition is not null and ought to be null if targetingCondition is not null
    private $leftChild;

    // ignored if targetingCondition is not null and ought to be null if targetingCondition is not null
    private $rightChild;

    // non null means this tree if a leaf
    private $targetingCondition;

    public function __construct($orOperator = null, $leftChild = null, $rightChild = null, $targetingCondition = null)
    {
        $this->orOperator = $orOperator;
        $this->leftChild = $leftChild;
        $this->rightChild = $rightChild;
        $this->targetingCondition = $targetingCondition;
    }

    public function getOrOperator()
    {
        return $this->orOperator;
    }

    public function setOrOperator($orOperator)
    {
        $this->orOperator = $orOperator;
    }

    public function getLeftChild()
    {
        return $this->leftChild;
    }

    public function setLeftChild($leftChild)
    {
        $this->leftChild = $leftChild;
    }

    public function getRightChild()
    {
        return $this->rightChild;
    }

    public function setRightChild($rightChild)
    {
        $this->rightChild = $rightChild;
    }

    public function getTargetingCondition()
    {
        return $this->targetingCondition;
    }

    public function setTargetingCondition($targetingCondition)
    {
        $this->targetingCondition = $targetingCondition;
    }
}
