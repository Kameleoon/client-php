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
        $pageView = $this->getLastTargetingData($data, "Kameleoon\Data\PageView");
        return $pageView !== null && $this->checkTargeting($pageView->getUrl());
    }
}
