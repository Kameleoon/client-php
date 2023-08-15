<?php

namespace Kameleoon\Network;

use Kameleoon\KameleoonClientImpl;

class ActivityEvent implements PostBodyLine
{
    public const EVENT_TYPE = "activity";

    private string $nonce;

    public function __construct()
    {
        $this->nonce = KameleoonClientImpl::obtainNonce();
    }

    public function obtainFullPostTextLine(): string
    {
        return (string)new QueryBuilder(
            new QueryParam(QueryParams::EVENT_TYPE, self::EVENT_TYPE),
            new QueryParam(QueryParams::NONCE, $this->nonce),
        );
    }
}
