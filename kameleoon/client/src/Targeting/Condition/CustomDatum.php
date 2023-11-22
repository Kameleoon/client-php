<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Helpers\StringHelper;

class CustomDatum extends TargetingCondition
{
    const TYPE = "CUSTOM_DATUM";

    private int $index;

    private string $operator;

    private ?string $value;

    public function getIndex()
    {
        return $this->index;
    }

    public function getOperator()
    {
        return $this->operator;
    }


    public function getValue()
    {
        return $this->value;
    }

    public function __construct($conditionData)
    {
        parent::__construct($conditionData);
        $this->index = intval($conditionData->customDataIndex);
        $this->operator = $conditionData->valueMatchType;
        $this->value = $conditionData->value;
    }

    public function check($data): bool
    {
        if (!is_iterable($data) || !isset($data[$this->index])) {
            return $this->operator === TargetingOperator::UNDEFINED;
        }

        $customData = $data[$this->index];
        return $this->checkTargeting($customData->getValues());
    }

    private function checkTargeting(array $customDataValues): bool
    {
        $targeting = false;
        switch ($this->operator) {
            case TargetingOperator::CONTAINS:
                $targeting = $this->checkForMatch(
                    $customDataValues,
                    function ($value) {
                        return strpos($value, $this->value) !== false;
                    }
                );
                break;
            case TargetingOperator::EXACT:
                $targeting = $this->checkForMatch(
                    $customDataValues,
                    function ($value) {
                        return $value == $this->value;
                    }
                );
                break;
            case TargetingOperator::REGULAR_EXPRESSION:
                $targeting = $this->checkForMatch(
                    $customDataValues,
                    function ($value) {
                        return preg_match("/" . $this->value . "/", $value);
                    }
                );
                break;
            case TargetingOperator::LOWER:
            case TargetingOperator::EQUAL:
            case TargetingOperator::GREATER:
                $targeting = $this->compareNumberValues($this->value, $customDataValues);
                break;
            case TargetingOperator::IS_TRUE:
                $targeting = $this->checkForMatch(
                    $customDataValues,
                    function ($value) {
                        return $value == "true";
                    }
                );
                break;
            case TargetingOperator::IS_FALSE:
                $targeting = $this->checkForMatch(
                    $customDataValues,
                    function ($value) {
                        return $value == "false";
                    }
                );
                break;
            case TargetingOperator::AMONG_VALUES:
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

    private function compareNumberValues(string $conditionValue, array $customDataValues): bool
    {
        $number = floatval($conditionValue);
        if (!is_nan($number)) {
            switch ($this->operator) {
                case TargetingOperator::LOWER:
                    return $this->checkForMatch(
                        $customDataValues,
                        function ($value) use ($number) {
                            return floatval($value) < $number;
                        }
                    );

                case TargetingOperator::EQUAL:
                    return $this->checkForMatch(
                        $customDataValues,
                        function ($value) use ($number) {
                            return floatval($value) == $number;
                        }
                    );

                case TargetingOperator::GREATER:
                    return $this->checkForMatch(
                        $customDataValues,
                        function ($value) use ($number) {
                            return floatval($value) > $number;
                        }
                    );
            }
        }
        return false;
    }
}
