<?php
namespace Kameleoon\Targeting;

use Kameleoon\Logging\KameleoonLogger;

class TargetingEngine
{
    public static function checkTargetingTree($targetingTree, callable $getTargetedData)
    {
        KameleoonLogger::debug("CALL: TargetingEngine::checkTargetingTree(targetingTree, getTargetedData)");
        $result = null;

		// checking if the tree has no targeting condition
		if (null == $targetingTree)
        {
            return true;
        }
        else
        {
            // checking if the tree is a leaf
            $targetingCondition = $targetingTree->getTargetingCondition();
			if (null != $targetingCondition)
            {
                $result = self::checkTargetingCondition($targetingCondition, $getTargetedData);
            }
            else
            {
                // computing left child result
                $leftChildResult = self::checkTargetingTree($targetingTree->getLeftChild(), $getTargetedData);

				$mustComputeRightChildResult = false;

				if (true == $leftChildResult)
                {
                    if (! $targetingTree->getOrOperator())
                    {
                        $mustComputeRightChildResult = true; // true AND anything, needs to know the anything
                    }
                }
                else if (false == $leftChildResult)
                {
                    if ($targetingTree->getOrOperator())
                    {
                        $mustComputeRightChildResult = true; // false OR anything, needs to know the anything
                    }
                }
                else if (null == $leftChildResult)
                {
                    $mustComputeRightChildResult = true; // (undefined OR anything) or (undefined AND anything), needs to know the anything
                }

				// computing right child result if we must do it
				$rightChildResult = null;
				if ($mustComputeRightChildResult)
                {
                    $rightChildResult = self::checkTargetingTree($targetingTree->getRightChild(), $getTargetedData);
                }

				// computing result
				if (true == $leftChildResult)
                {
                    if ($targetingTree->getOrOperator())
                    {
                        $result = true; // true OR anything
                    }
                    else
                    {
                        if (true == $rightChildResult)
                        {
                            $result = true; // true AND true
                        }
                        else if (false == $rightChildResult)
                        {
                            $result = false; // true AND false
                        }
                        else if (null == $rightChildResult)
                        {
                            $result = null; // true AND undefined
                        }
                    }
                }
                else if (false == $leftChildResult)
                {
                    if ($targetingTree->getOrOperator())
                    {
                        if (true == $rightChildResult)
                        {
                            $result = true; // false OR true
                        }
                        else if (false == $rightChildResult)
                        {
                            $result = false; // false OR false
                        }
                        else if (null == $rightChildResult)
                        {
                            $result = null; // false OR undefined
                        }
                    }
                    else
                    {
                        $result = false; // false AND anything
                    }
                }
                else if (null == $leftChildResult)
                {
                    if ($targetingTree->getOrOperator())
                    {
                        if (true == $rightChildResult)
                        {
                            $result = true; // undefined OR true
                        }
                        else if (false == $rightChildResult)
                        {
                            $result = null; // undefined OR false
                        }
                        else if (null == $rightChildResult)
                        {
                            $result = null; // undefined OR undefined
                        }
                    }
                    else
                    {
                        if (true == $rightChildResult)
                        {
                            $result = null; // undefined AND true
                        }
                        else if (false == $rightChildResult)
                        {
                            $result = false; // undefined AND false
                        }
                        else if (null == $rightChildResult)
                        {
                            $result = null; // undefined AND undefined
                        }
                    }
                }
			}
		}
        KameleoonLogger::debug(
            "RETURN: TargetingEngine::checkTargetingTree(targetingTree, getTargetedData) -> (result: %s)", $result);
		// returning result
		return $result;
	}

	// check targeting condition
	private static function checkTargetingCondition($targetingCondition, callable $getTargetedData)
    {
        $result = null;

		// obtaining targeting
		if ($targetingCondition != null)
        {
            $result = $targetingCondition->check($getTargetedData($targetingCondition->getType()));

            // correcting targeting result in the case an exclusion rule is asked for
            if (true != $targetingCondition->getInclude())
            {
                if (null == $result)
                {
                    $result = true;
                }
                else
                {
                    $result = !$result;
                }
            }
        }
        else
        {
            $result = true;
        }
        KameleoonLogger::debug("Targeting condition with id %s is %s", $targetingCondition->getId(), $result);
		// returning result
		return $result;
	}
}
