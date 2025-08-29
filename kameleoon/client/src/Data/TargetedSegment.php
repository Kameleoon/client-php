<?php

declare(strict_types=1);

namespace Kameleoon\Data;

use Kameleoon\Data\BaseData;
use Kameleoon\Network\QueryBuilder;
use Kameleoon\Network\QueryParam;
use Kameleoon\Network\QueryParams;
use Kameleoon\Network\Sendable;

/** @internal */
final class TargetedSegment extends Sendable implements BaseData
{
    public const EVENT_TYPE = "targetingSegment";

    private int $id;

    public function __construct(int $id)
    {
        $this->id = $id;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getQuery(): string
    {
        return (string)new QueryBuilder(
            new QueryParam(QueryParams::EVENT_TYPE, self::EVENT_TYPE),
            new QueryParam(QueryParams::SEGMENT_ID, (string)$this->id),
            new QueryParam(QueryParams::NONCE, $this->getNonce()),
        );
    }

    public function __toString(): string
    {
        return "TargetedSegment{id:$this->id}";
    }
}
