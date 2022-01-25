<?php
namespace Kameleoon\Data;

use Kameleoon\KameleoonClientImpl;
use Kameleoon\Helpers\URLEncoding;

class CustomData implements DataInterface
{
    private $id;
    private $value;
    private $nonce;

    public function __construct($id, string $value)
    {
        $this->id = $id;
        $this->value = $value;
        $this->nonce = KameleoonClientImpl::obtainNonce();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function obtainFullPostTextLine()
    {
        $encoded = URLEncoding::encodeURIComponent(json_encode(array(array($this->value, 1)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $encoded = str_replace("%5C", "", $encoded);
        return "eventType=customData&index=" . $this->id . "&valueToCount=" . $encoded . "&overwrite=true&nonce=" . $this->nonce;
    }
}