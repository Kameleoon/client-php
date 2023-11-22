<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

class PageUrlCondition extends StringValueCondition
{
    const TYPE = "PAGE_URL";

    public function __construct($conditionData)
    {
        parent::__construct($conditionData, $conditionData->url ?? null);
    }

    public function check($data): bool
    {
        if (is_iterable($data)) {
            if ($this->operator === TargetingOperator::EXACT) {
                return !is_null($this->conditionValue) && isset($data[$this->conditionValue]);
            } else {
                foreach ($data as $pair) {
                    if ($this->checkTargeting($pair[0]->getUrl())) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
