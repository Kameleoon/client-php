<?php
namespace Kameleoon\Data;

use Device\DeviceType;
use Kameleoon\KameleoonClientImpl;

class Device implements DataInterface
{
    public const PHONE = "PHONE";
    public const TABLET = "TABLET";
    public const DESKTOP = "DESKTOP";

    private string $type;
    private $nonce;

    public function __construct(string $type)
    {
        $this->type = $type;
        $this->nonce = KameleoonClientImpl::obtainNonce();
    }

    public function obtainFullPostTextLine ()
    {
        return "eventType=staticData&deviceType=" . $this->type . "&nonce=" . $this->nonce;
    }
}
