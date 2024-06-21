<?php

declare(strict_types=1);

namespace Kameleoon\Data;

class VisitorVisits implements BaseData
{
    private array $previousVisitTimestamps;

    public function __construct(array $previousVisitTimestamps)
    {
        $this->previousVisitTimestamps = $previousVisitTimestamps;
    }

    public static function getPreviousVisitTimestamps(?VisitorVisits $visitorVisits): array
    {
        return ($visitorVisits !== null) ? $visitorVisits->previousVisitTimestamps : [];
    }

    public static function isVisitorVisits($obj): bool
    {
        return ($obj === null) || ($obj instanceof VisitorVisits);
    }
}
