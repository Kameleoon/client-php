<?php

namespace Kameleoon\Data;

use Kameleoon\KameleoonClientImpl;
use Kameleoon\Network\QueryBuilder;
use Kameleoon\Network\QueryParam;
use Kameleoon\Network\QueryParams;

class PageView implements DataInterface
{
    public const EVENT_TYPE = "page";

    private ?string $url;
    private ?string $title;
    private ?array $referrers;
    private string $nonce;

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function __construct(?string $url, ?string $title, ?array $referrers = null)
    {
        $this->url = $url;
        $this->title = $title;
        $this->referrers = $referrers;
        $this->nonce = KameleoonClientImpl::obtainNonce();
    }

    public function obtainFullPostTextLine(): string
    {
        $qb = new QueryBuilder(
            new QueryParam(QueryParams::EVENT_TYPE, self::EVENT_TYPE),
            new QueryParam(QueryParams::HREF, $this->url),
            new QueryParam(QueryParams::TITLE, $this->title),
            new QueryParam(QueryParams::NONCE, $this->nonce),
        );
        if ($this->referrers != null) {
            $strReferrers = "[" . implode(",", $this->referrers) . "]";
            $qb->append(new QueryParam(QueryParams::REFERRERS_INDICES, $strReferrers));
        }
        return (string)$qb;
    }
}
