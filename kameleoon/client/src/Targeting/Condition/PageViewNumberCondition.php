<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Data\Manager\PageViewVisit;

class PageViewNumberCondition extends NumberCondition
{
    const TYPE = "PAGE_VIEWS";

    public function __construct($conditionData)
    {
        parent::__construct($conditionData, $conditionData->pageCount ?? 0x7FFFFFFF);
    }

    public function check($data): bool
    {
        if (is_array($data)) {
            $count = 0;
            foreach ($data as $url => $visit) {
                if ($visit instanceof PageViewVisit) {
                    $count += $visit->getCount();
                }
            }
            return $this->checkTargeting($count);
        }
        return false;
    }
}
