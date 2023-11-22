<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

class PageTitleCondition extends StringValueCondition
{
    const TYPE = "PAGE_TITLE";

    public function __construct($conditionData)
    {
        parent::__construct($conditionData, $conditionData->title ?? null);
    }

    public function check($data): bool
    {
        if (is_iterable($data)) {
            foreach ($data as $pageView) {
                if ($this->checkTargeting($pageView->getTitle())) {
                    return true;
                }
            }
        }
        return false;
    }
}
