<?php

namespace Kameleoon\Targeting;

use Iterator;

class TargetingTreeBuilder
{
    /** Built from targeting segment builder parameter map. */
    public function createTargetingTree($conditions)
    {
        if ($conditions == null) {
            return null;
        }

        $conditionsFirstLevel = array();
        $jArrayConditionsFirstLevel = $conditions->firstLevel;
        if ($jArrayConditionsFirstLevel != null) {
            foreach ($jArrayConditionsFirstLevel as $firstLevel) {
                array_push($conditionsFirstLevel, $firstLevel);
            }
        }

        $firstLevelIterator = new KIterator($conditionsFirstLevel);
        $firstLevelOrOperatorsIterator = null;
        $firstLevelOrOperators = array();
        $jArrayFirstLevelOrOperators = $conditions->firstLevelOrOperators;

        if ($jArrayFirstLevelOrOperators != null) {
            for ($i = 0; $i < count($jArrayFirstLevelOrOperators); $i++) {
                array_push($firstLevelOrOperators, $jArrayFirstLevelOrOperators[$i]);
            }
        }
        if ($firstLevelIterator->hasNext()) {
            $firstLevelOrOperatorsIterator = new KIterator($firstLevelOrOperators);
        } else {
            $firstLevelOrOperatorsIterator = null;
        }

        return $this->createTargetingTreeFirstLevel($firstLevelOrOperatorsIterator, $firstLevelIterator);
    }

    private function createTargetingTreeFirstLevel($firstLevelOrOperatorsIterator, $firstLevelIterator)
    {
        if ($firstLevelIterator->hasNext()) {
            $leftFirstLevelMap = $firstLevelIterator->nextObject();

            $conditions = array();
            $jArrayConditions = $leftFirstLevelMap->conditions;
            if ($jArrayConditions != null) {
                for ($i = 0; $i < count($jArrayConditions); $i++) {
                    array_push($conditions, $jArrayConditions[$i]);
                }
            }
            $orOperators = array();
            $jArrayOrOperators = $leftFirstLevelMap->orOperators;
            if ($jArrayOrOperators != null) {
                for ($j = 0; $j < count($jArrayOrOperators); $j++) {
                    array_push($orOperators, $jArrayOrOperators[$j]);
                }
            }
            $leftChild = $this->createTargetingTreeSecondLevel(new KIterator($orOperators), new KIterator($conditions));

            if ($firstLevelIterator->hasNext()) {
                $orOperator = $firstLevelOrOperatorsIterator->nextObject();
                if ($orOperator) {
                    return new TargetingTree(
                        $orOperator,
                        $leftChild,
                        $this->createTargetingTreeFirstLevel($firstLevelOrOperatorsIterator, $firstLevelIterator),
                        null
                    );
                }

                $rightFirstLevelMap = $firstLevelIterator->nextObject();

                $rightConditions = array();
                $jArrayRightConditions = $rightFirstLevelMap->conditions;
                if ($jArrayRightConditions != null) {
                    for ($k = 0; $k < count($jArrayRightConditions); $k++) {
                        array_push($rightConditions, $jArrayRightConditions[$k]);
                    }
                }
                $rightOrOperators = array();
                $jArrayRightOrOperators = $rightFirstLevelMap->orOperators;
                if ($jArrayRightOrOperators != null) {
                    for ($p = 0; $p < count($jArrayRightOrOperators); $p++) {
                        array_push($rightOrOperators, $jArrayRightOrOperators[$p]);
                    }
                }
                $rightChild = $this->createTargetingTreeSecondLevel(
                    new KIterator($rightOrOperators),
                    new KIterator($rightConditions)
                );
                $leftAndRightChildren = new TargetingTree($orOperator, $leftChild, $rightChild, null);
                if ($firstLevelIterator->hasNext()) {
                    return new TargetingTree(
                        $firstLevelOrOperatorsIterator->nextObject(),
                        $leftAndRightChildren,
                        $this->createTargetingTreeFirstLevel($firstLevelOrOperatorsIterator, $firstLevelIterator),
                        null
                    );
                }
                return $leftAndRightChildren;
            }
            return $leftChild;
        }
        return null;
    }

    private function createTargetingTreeSecondLevel($secondLevelOrOperatorsIterator, $secondLevelConditionsIterator)
    {
        if ($secondLevelConditionsIterator->hasNext()) {
            $leftChild = new TargetingTree();
            $condition = $secondLevelConditionsIterator->nextObject();
            $targetingCondition = TargetingConditionsFactory::getCondition($condition);
            $leftChild->setTargetingCondition($targetingCondition);

            if ($secondLevelConditionsIterator->hasNext()) {
                $orOperator = $secondLevelOrOperatorsIterator->nextObject();
                if ($orOperator) {
                    return new TargetingTree(
                        $orOperator,
                        $leftChild,
                        $this->createTargetingTreeSecondLevel(
                            $secondLevelOrOperatorsIterator,
                            $secondLevelConditionsIterator
                        ),
                        null
                    );
                }

                $rightChild = new TargetingTree();
                $rightCondition = $secondLevelConditionsIterator->nextObject();
                $rightTargetingCondition = TargetingConditionsFactory::getCondition($rightCondition);
                $rightChild->setTargetingCondition($rightTargetingCondition);

                $leftAndRightChildren = new TargetingTree($orOperator, $leftChild, $rightChild, null);
                if ($secondLevelConditionsIterator->hasNext()) {
                    return new TargetingTree(
                        $secondLevelOrOperatorsIterator->nextObject(),
                        $leftAndRightChildren,
                        $this->createTargetingTreeSecondLevel(
                            $secondLevelOrOperatorsIterator,
                            $secondLevelConditionsIterator
                        ),
                        null
                    );
                }
                return $leftAndRightChildren;
            }
            return $leftChild;
        }
        return null;
    }
}

class KIterator implements Iterator
{
    protected $array;
    protected $i = -1;

    public function __construct(array $array)
    {
        $this->array = $array;
    }
    public function rewind(): void
    {
        $this->i = -1;
    }
    public function valid(): bool
    {
        return isset($this->array[$this->i]);
    }
    public function next(): void
    {
        $this->i++;
    }
    #[\ReturnTypeWillChange]
    public function nextObject()
    {
        $this->next();
        return $this->current();
    }
    public function hasNext()
    {
        return count($this->array) > ($this->i + 1);
    }
    #[\ReturnTypeWillChange]
    public function key()
    {
        return $this->i;
    }
    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->array[$this->i];
    }
}
