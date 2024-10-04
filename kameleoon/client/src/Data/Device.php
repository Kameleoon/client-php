<?php

namespace Kameleoon\Data;

use Kameleoon\Network\QueryBuilder;
use Kameleoon\Network\QueryParam;
use Kameleoon\Network\QueryParams;
use Kameleoon\Network\Sendable;

class Device extends Sendable implements Data
{
    public const EVENT_TYPE = "staticData";

    public const PHONE = "PHONE";
    public const TABLET = "TABLET";
    public const DESKTOP = "DESKTOP";

    private string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getQuery(): string
    {
        return (string)new QueryBuilder(
            new QueryParam(QueryParams::EVENT_TYPE, self::EVENT_TYPE),
            new QueryParam(QueryParams::DEVICE_TYPE, $this->type),
            new QueryParam(QueryParams::NONCE, $this->getNonce()),
        );
    }

    public function __toString(): string
    {
        return "Device{deviceType:'$this->type'}";
    }
}
