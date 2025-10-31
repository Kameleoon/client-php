<?php

namespace Kameleoon\Data;

use Kameleoon\Network\QueryBuilder;
use Kameleoon\Network\QueryParam;
use Kameleoon\Network\QueryParams;
use Kameleoon\Network\QueryPresentable;
use Kameleoon\Network\Sendable;

class PageView extends Sendable implements Data
{
    public const EVENT_TYPE = "page";

    private string $url;
    private ?string $title;
    private ?array $referrers;

    public function __construct(string $url, ?string $title = null, ?array $referrers = null)
    {
        $this->url = $url;
        $this->title = $title;
        $this->referrers = $referrers;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /** @internal */
    public function getQuery(): string
    {
        $qb = new QueryBuilder(
            new QueryParam(QueryParams::EVENT_TYPE, self::EVENT_TYPE),
            new QueryParam(QueryParams::HREF, $this->url),
            new QueryParam(QueryParams::TITLE, $this->title),
            new QueryParam(QueryParams::NONCE, $this->getNonce()),
        );
        if ($this->referrers != null) {
            $strReferrers = "[" . implode(",", $this->referrers) . "]";
            $qb->append(new QueryParam(QueryParams::REFERRERS_INDICES, $strReferrers));
        }
        return (string)$qb;
    }

    public function __toString(): string
    {
        return "PageView{url:'" . $this->url . "',title:'" . $this->title . "',referrers:" .
            json_encode($this->referrers) . "}";
    }
}
