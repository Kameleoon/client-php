<?php

namespace Kameleoon\Targeting\Conditions;

use Kameleoon\Helpers\StringHelper;
use Kameleoon\Targeting\TargetingCondition;

class CustomDatum extends TargetingCondition
{
    const TYPE = "CUSTOM_DATUM";

    private $index;

    private $operator;

    private $value;

    public function getIndex()
    {
        return $this->index;
    }

    public function setIndex($index)
    {
        $this->index = $index;
    }

    public function getOperator()
    {
        return $this->operator;
    }

    public function setOperator($operator)
    {
        $this->operator = $operator;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function check($targetingDatas): bool
    {
        $customData = null;
        foreach ($targetingDatas as $targetingData) {
            $data = $targetingData->getData();
            if (get_class($data) == "Kameleoon\Data\CustomData") {
                $custom = $data;
                if ($custom->getId() == $this->index) {
                    $customData = $custom;
                }
            }
        }

        if ($customData == null) {
            return $this->operator == "UNDEFINED";
        }
        return $this->checkTargeting($customData->getValues());

    }

    private function checkTargeting(array $customDataValues): bool
    {
        $targeting = false;
        switch ($this->operator) {
            case "CONTAINS":
                $targeting = $this->checkForMatch(
                    $customDataValues,
                    function ($value) {
                        return strpos($value, $this->value) !== false;
                    }
                );
                break;
            case "EXACT":
                $targeting = $this->checkForMatch(
                    $customDataValues,
                    function ($value) {
                        return $value == $this->value;
                    }
                );
                break;
            case "REGULAR_EXPRESSION":
                $targeting = $this->checkForMatch(
                    $customDataValues,
                    function ($value) {
                        return preg_match("/" . $this->value . "/", $value);
                    }
                );
                break;
            case "LOWER":
            case "EQUAL":
            case "GREATER":
                $number = floatval($this->value);
                if (!is_nan($number)) {
                    switch ($this->operator) {
                        case "LOWER":
                            $targeting = $this->checkForMatch(
                                $customDataValues,
                                function ($value) use ($number) {
                                    return floatval($value) < $number;
                                }
                            );
                            break;
                        case "EQUAL":
                            $targeting = $this->checkForMatch(
                                $customDataValues,
                                function ($value) use ($number) {
                                    return floatval($value) == $number;
                                }
                            );
                            break;
                        case "GREATER":
                            $targeting = $this->checkForMatch(
                                $customDataValues,
                                function ($value) use ($number) {
                                    return floatval($value) > $number;
                                }
                            );
                            break;
                        default:
                            break;
                    }
                }
                break;
            case "TRUE":
                $targeting = $this->checkForMatch(
                    $customDataValues,
                    function ($value) {
                        return $value == "true";
                    }
                );
                break;
            case "FALSE":
                $targeting = $this->checkForMatch(
                    $customDataValues,
                    function ($value) {
                        return $value == "false";
                    }
                );
                break;
            case "AMONG_VALUES":
                $conditionValues = array_reduce(
                    json_decode($this->value),
                    function ($res, $key) {
                        $res[StringHelper::strval($key)] = true;
                        return $res;
                    },
                    []
                );

                $targeting = $this->checkForMatch(
                    $customDataValues,
                    function ($value) use ($conditionValues) {
                        return isset($conditionValues[$value]);
                    }
                );
                break;
            default:
                break;
        }
        return $targeting;
    }

    private function checkForMatch(array $customDataValues, callable $func): bool
    {
        foreach ($customDataValues as $value) {
            if ($func($value)) {
                return true;
            }
        }
        return false;
    }
}
