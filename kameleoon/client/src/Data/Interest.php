<?php
namespace Kameleoon\Data;

use Kameleoon\KameleoonClientImpl;

class Interest implements DataInterface
{
    private $index;
    private $nonce;

    public function __construct($index)
    {
        $this->index = $index;
        $this->nonce = KameleoonClientImpl::obtainNonce();
    }

    public function obtainFullPostTextLine ()
    {
        return "eventType=interests&indexes=[" . $this->index . "]&fresh=true&nonce=" . $this->nonce;
    }
}