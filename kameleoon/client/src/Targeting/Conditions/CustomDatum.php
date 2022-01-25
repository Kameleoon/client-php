<?php
namespace Kameleoon\Targeting\Conditions;

use Kameleoon\Targeting\TargetingCondition;

class CustomDatum extends TargetingCondition
{
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

    public function check($targetingDatas)
    {
        $customDatum = null;
        foreach ($targetingDatas as $targetingData) {
            $data = $targetingData->getData();
            if (get_class($data) == "Kameleoon\Data\CustomData") {
                $custom = $data;
                if ($custom->getId() == $this->index) {
                    $customDatum = $custom;
                }
            }
        }

        if ($customDatum == null) {
            $targeting = $this->operator == "UNDEFINED";
        } else {
            $targeting = false;
            switch ($this->operator) {
                case "CONTAINS":
                    if (strpos($customDatum->getValue(), $this->value) !== false) {
                        $targeting = true;
                    }
                    break;
                case "EXACT":
                    if ($customDatum->getValue() == $this->value) {
                        $targeting = true;
                    }
                    break;
                case "REGULAR_EXPRESSION":
                    if (preg_match("/" . $this->value . "/", $customDatum->getValue())) {
                        $targeting = true;
                    }
                    break;
                case "LOWER":
                case "EQUAL":
                case "GREATER":
                    $number = floatval($this->value);
                    if (!is_nan($number)) {
                        $valueNumber = floatval($customDatum->getValue());
                        if (!is_nan($valueNumber)) {
                            switch ($this->operator) {

                                case "LOWER":
                                    if ($valueNumber < $number) {
                                        $targeting = true;
                                    }
                                    break;
                                case "EQUAL":
                                    if ($valueNumber == $number) {
                                        $targeting = true;
                                    }
                                    break;
                                case "GREATER":
                                    if ($valueNumber > $number) {
                                        $targeting = true;
                                    }
                                    break;
                            }
                        }
                    }
                    break;
                case "TRUE":
                    if ($customDatum->getValue() == "true") {
                        $targeting = true;
                    }
                    break;
                case "FALSE":
                    if ($customDatum->getValue() == "false") {
                        $targeting = true;
                    }
                    break;
            }
        }

        return $targeting;
    }
}
