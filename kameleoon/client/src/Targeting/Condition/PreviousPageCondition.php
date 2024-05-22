<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Data\Manager\PageViewVisit;

class PreviousPageCondition extends StringValueCondition
{
    const TYPE = "PREVIOUS_PAGE";

    public function __construct($conditionData)
    {
        parent::__construct($conditionData, $conditionData->url ?? "");
    }

    public function check($data): bool
    {
        if (!is_array($data)) {
            return false;
        }
        $mostRecentVisit = null;
        $secondMostRecentVisit = null;
        foreach ($data as $url => $visit) {
            if (!($visit instanceof PageViewVisit)) {
                continue;
            }
            $ts = $visit->getLastTimestamp();
            if (($mostRecentVisit === null) || ($ts > $mostRecentVisit->getLastTimestamp())) {
                $secondMostRecentVisit = $mostRecentVisit;
                $mostRecentVisit = $visit;
            } else if (($secondMostRecentVisit === null) || ($ts > $secondMostRecentVisit->getLastTimestamp())) {
                $secondMostRecentVisit = $visit;
            }
        }
        return ($secondMostRecentVisit !== null) &&
            $this->checkTargeting($secondMostRecentVisit->getPageView()->getUrl());
    }
}
