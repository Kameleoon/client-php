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
        if (is_array($data) && !empty($data)) {
            $latest = null;
            foreach ($data as $visit) {
                if ($latest === null || $visit->getLastTimestamp() > $latest->getLastTimestamp()) {
                    $latest = $visit;
                }
            }
            return $latest !== null && $this->checkTargeting($latest->getPageView()->getUrl());
        }
        return false;
    }
}
