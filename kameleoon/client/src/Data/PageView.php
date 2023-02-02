<?php
namespace Kameleoon\Data;

use Kameleoon\KameleoonClientImpl;
use Kameleoon\Helpers\URLEncoding;

class PageView implements DataInterface
{
    private $url;
    private $title;
    private $referrers;
    private $nonce;

    public function __construct($url, $title, array $referrers = null)
    {
        $this->url = $url;
        $this->title = $title;
        $this->referrers = $referrers;
        $this->nonce = KameleoonClientImpl::obtainNonce();
    }

    public function obtainFullPostTextLine ()
    {
        return "eventType=page&href=" . URLEncoding::encodeURIComponent($this->url) . "&title=" . URLEncoding::encodeURIComponent($this->title) . ($this->referrers == null ? "" : "&referrersIndices=[" . implode(",", $this->referrers) . "]") . "&nonce=" . $this->nonce;
    }
}