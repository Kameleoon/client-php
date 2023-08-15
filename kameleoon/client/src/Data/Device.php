<?php

namespace Kameleoon\Data;

use Kameleoon\KameleoonClientImpl;
use Kameleoon\Network\QueryBuilder;
use Kameleoon\Network\QueryParam;
use Kameleoon\Network\QueryParams;

class Device implements DataInterface
{
    public const EVENT_TYPE = "staticData";

    public const PHONE = "PHONE";
    public const TABLET = "TABLET";
    public const DESKTOP = "DESKTOP";

    private string $type;
    private $nonce;

    public function getType(): string
    {
        return $this->type;
    }

    public function __construct(string $type)
    {
        $this->type = $type;
        $this->nonce = KameleoonClientImpl::obtainNonce();
    }

    public function obtainFullPostTextLine(): string
    {
        return (string)new QueryBuilder(
            new QueryParam(QueryParams::EVENT_TYPE, self::EVENT_TYPE),
            new QueryParam(QueryParams::DEVICE_TYPE, $this->type),
            new QueryParam(QueryParams::NONCE, $this->nonce),
        );
    }
}
