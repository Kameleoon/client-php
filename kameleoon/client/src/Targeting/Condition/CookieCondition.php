<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Data\Cookie;
use Kameleoon\Logging\KameleoonLogger;

class CookieCondition extends TargetingCondition
{
    const TYPE = "COOKIE";

    private string $conditionName;
    private string $nameMatchType;
    private string $conditionValue;
    private string $valueMatchType;

    public function __construct($conditionData)
    {
        parent::__construct($conditionData);
        $this->conditionName = $conditionData->name ?? "";
        $this->nameMatchType = $conditionData->nameMatchType ?? TargetingOperator::UNKNOWN;
        $this->conditionValue = $conditionData->value ?? "";
        $this->valueMatchType = $conditionData->valueMatchType ?? TargetingOperator::UNKNOWN;
    }

    public function check($data): bool
    {
        return ($data instanceof Cookie) && $this->checkValues($this->selectValues($data));
    }

    private function selectValues(Cookie $cookie): array
    {
        switch ($this->nameMatchType) {
            case TargetingOperator::EXACT:
                $value = $cookie->getCookies()[$this->conditionName] ?? null;
                return ($value !== null) ? [$value] : [];
            case TargetingOperator::CONTAINS:
                $values = [];
                foreach ($cookie->getCookies() as $name => $value) {
                    if (strpos($name, $this->conditionName) !== false) {
                        array_push($values, $value);
                    }
                }
                return $values;
            case TargetingOperator::REGULAR_EXPRESSION:
                $values = [];
                $pattern = "/" . $this->conditionName . "/";
                foreach ($cookie->getCookies() as $name => $value) {
                    if (preg_match($pattern, $name)) {
                        array_push($values, $value);
                    }
                }
                return $values;
            default:
                KameleoonLogger::error("Unexpected comparing operation for 'Cookie' condition (name): '%s'",
                    $this->nameMatchType);
                return [];
        }
    }

    private function checkValues(array $values): bool
    {
        switch ($this->valueMatchType) {
            case TargetingOperator::EXACT:
                foreach ($values as $value) {
                    if ($value == $this->conditionValue) {
                        return true;
                    }
                }
                return false;
            case TargetingOperator::CONTAINS:
                foreach ($values as $value) {
                    if (strpos($value, $this->conditionValue) !== false) {
                        return true;
                    }
                }
                return false;
            case TargetingOperator::REGULAR_EXPRESSION:
                $pattern = "/" . $this->conditionValue . "/";
                foreach ($values as $value) {
                    if (preg_match($pattern, $value)) {
                        return true;
                    }
                }
                return false;
            default:
                KameleoonLogger::error("Unexpected comparing operation for 'Cookie' condition (value): '%s'",
                    $this->valueMatchType);
                return false;
        }
    }
}
