<?php

namespace Kameleoon\Network;

class ActivityEvent extends Sendable
{
    public const EVENT_TYPE = "activity";

    public function getQuery(): string
    {
        return (string)new QueryBuilder(
            new QueryParam(QueryParams::EVENT_TYPE, self::EVENT_TYPE),
            new QueryParam(QueryParams::NONCE, $this->getNonce()),
        );
    }
}
