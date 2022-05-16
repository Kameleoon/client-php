<?php
namespace Kameleoon\Data;

use Kameleoon\KameleoonClientImpl;
use Kameleoon\Helpers\URLEncoding;

class PageView implements DataInterface
{
    private $url;
    private $title;
    private $referrer;
    private $nonce;

    public function __construct($url, $title, int $referrer = null)
    {
        $this->url = $url;
        $this->title = $title;
        $this->referrer = $referrer;
        $this->nonce = KameleoonClientImpl::obtainNonce();
    }

    public function obtainFullPostTextLine ()
    {
        return "eventType=page&href=" . URLEncoding::encodeURIComponent($this->url) . "&title=" . URLEncoding::encodeURIComponent($this->title) . ($this->referrer == null ? "" : "&referrers=[" . $this->referrer . "]") . "&nonce=" . $this->nonce;
    }
}