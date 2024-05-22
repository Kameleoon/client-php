<?php

namespace Kameleoon\Data;

class VisitorVisits implements BaseData
{
    private array $previousVisitTimestamps;

    public function __construct(array $previousVisitTimestamps)
    {
        $this->previousVisitTimestamps = $previousVisitTimestamps;
    }

    public function getPreviousVisitTimestamps(): array
    {
        return $this->previousVisitTimestamps;
    }
}
