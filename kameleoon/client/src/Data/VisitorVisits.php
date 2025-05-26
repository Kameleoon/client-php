<?php

declare(strict_types=1);

namespace Kameleoon\Data;

use Kameleoon\Helpers\StringHelper;
use Kameleoon\Network\QueryBuilder;
use Kameleoon\Network\QueryParam;
use Kameleoon\Network\QueryParams;
use Kameleoon\Network\Sendable;

final class VisitorVisits extends Sendable implements BaseData
{
    public const EVENT_TYPE = "staticData";

    private int $visitNumber;
    private array $prevVisits;
    private int $timeStarted;
    private int $timeSincePreviousVisit;

    public function __construct(array $prevVisits, int $visitNumber = 0)
    {
        $this->visitNumber = max($visitNumber, count($prevVisits));
        $this->prevVisits = $prevVisits;
        $this->timeStarted = 0;
        $this->timeSincePreviousVisit = 0;
    }

    public function localize(int $timeStarted): VisitorVisits
    {
        $timeSincePreviousVisit = 0;
        foreach ($this->prevVisits as $visit) {
            $timeDelta = $timeStarted - $visit->getTimeLastActivity();
            if ($timeDelta >= 0) {
                $timeSincePreviousVisit = $timeDelta;
                break;
            }
        }
        $localized = new VisitorVisits($this->prevVisits, $this->visitNumber);
        $localized->timeStarted = $timeStarted;
        $localized->timeSincePreviousVisit = $timeSincePreviousVisit;
        return $localized;
    }

    public function getVisitNumber(): int
    {
        return $this->visitNumber;
    }

    public function getPrevVisits(): array
    {
        return $this->prevVisits;
    }

    public function getTimeStarted(): int
    {
        return $this->timeStarted;
    }

    public function getTimeSincePreviousVisit(): int
    {
        return $this->timeSincePreviousVisit;
    }

    public function getQuery(): string
    {
        return (string)new QueryBuilder(
            new QueryParam(QueryParams::EVENT_TYPE, self::EVENT_TYPE),
            new QueryParam(QueryParams::VISIT_NUMBER, (string)$this->visitNumber),
            new QueryParam(QueryParams::TIME_SINCE_PREVIOUS_VISIT, (string)$this->timeSincePreviousVisit),
            new QueryParam(QueryParams::NONCE, $this->getNonce()),
        );
    }

    public function __toString(): string
    {
        return "VisitorVisits{" .
            "visitNumber:" . $this->visitNumber .
            ",prevVisits:" . StringHelper::sarray($this->prevVisits) .
            ",timeStarted:" . $this->timeStarted .
            ",timeSincePreviousVisit:" . $this->timeSincePreviousVisit .
            "}";
    }

    public static function getVisitorVisits(?VisitorVisits $visitorVisits): VisitorVisits
    {
        return $visitorVisits ?? new VisitorVisits([]);
    }

    public static function tryGetVisitorVisits($obj, ?VisitorVisits &$visitorVisits): bool
    {
        if ($obj instanceof VisitorVisits) {
            $visitorVisits = $obj;
            return true;
        }
        if ($obj === null) {
            $visitorVisits = new VisitorVisits([]);
            return true;
        }
        return false;
    }
}
