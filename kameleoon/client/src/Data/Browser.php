<?php
namespace Kameleoon\Data;

use Kameleoon\KameleoonClientImpl;

class Browser implements DataInterface {

    public static $browsers = array("CHROME"=>0, "INTERNET_EXPLORER"=>1, "FIREFOX"=>2, "SAFARI"=>3, "OPERA"=>4, "OTHER"=>5);
    private $browser;
    private $nonce;

    public function __construct($browser)
    {
        $this->browser = $browser;
        $this->nonce = KameleoonClientImpl::obtainNonce();
    }

    public function obtainFullPostTextLine()
    {
        return "eventType=staticData&browser=" . $this->browser . "&nonce=" . $this->nonce;
    }
}