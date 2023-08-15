<?php

namespace Kameleoon\Network;

use Kameleoon\KameleoonClientImpl;

class ExperimentEvent implements PostBodyLine
{
    public const EVENT_TYPE = "experiment";

    private int $experimentId;
    private int $variationId;
    private string $nonce;

    public function __construct(int $experimentId, int $variationId)
    {
        $this->experimentId = $experimentId;
        $this->variationId = $variationId;
        $this->nonce = KameleoonClientImpl::obtainNonce();
    }

    public function obtainFullPostTextLine(): string
    {
        return (string)new QueryBuilder(
            new QueryParam(QueryParams::EVENT_TYPE, self::EVENT_TYPE),
            new QueryParam(QueryParams::EXPERIMENT_ID, (string)$this->experimentId),
            new QueryParam(QueryParams::VARIATION_ID, (string)$this->variationId),
            new QueryParam(QueryParams::NONCE, $this->nonce),
        );
    }
}
