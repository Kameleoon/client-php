<?php

namespace Kameleoon\Data;

use Kameleoon\KameleoonClientImpl;
use Kameleoon\Network\QueryBuilder;
use Kameleoon\Network\QueryParam;
use Kameleoon\Network\QueryParams;

class Conversion implements DataInterface
{
    public const EVENT_TYPE = "conversion";

    private int $goalId;
    private $revenue;
    private $negative;
    private $nonce;

    public function getGoalId(): int
    {
        return $this->goalId;
    }

    public function __construct(int $goalId, float $revenue = 0, bool $negative = false)
    {
        $this->goalId = $goalId;
        $this->revenue = $revenue;
        $this->negative = $negative;
        $this->nonce = KameleoonClientImpl::obtainNonce();
    }

    public function obtainFullPostTextLine(): string
    {
        return (string)new QueryBuilder(
            new QueryParam(QueryParams::EVENT_TYPE, self::EVENT_TYPE),
            new QueryParam(QueryParams::GOAL_ID, (string)$this->goalId),
            new QueryParam(QueryParams::REVENUE, (string)$this->revenue),
            new QueryParam(QueryParams::NEGATIVE, $this->negative ? "true" : "false"),
            new QueryParam(QueryParams::NONCE, $this->nonce),
        );
    }
}
