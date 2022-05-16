<?php
namespace Kameleoon\Data;

use Device\DeviceType;
use Kameleoon\KameleoonClientImpl;

class Device implements DataInterface
{
    public const PHONE = 0;
    public const TABLET = 1;
    public const DESKTOP = 2;

    private const PHONE_STRING = "PHONE";
    private const TABLET_STRING = "TABLET";
    private const DESKTOP_STRING = "DESKTOP";
    
    private string $type; 
    private $nonce;

    public function __construct(int $type)
    {
        $this->type = $this->convertType($type);
        $this->nonce = KameleoonClientImpl::obtainNonce();
    }

    public function obtainFullPostTextLine ()
    {
        return "eventType=staticData&deviceType=" . $this->type . "&nonce=" . $this->nonce;
    }

    private function convertType(int $type) {
        switch ($type) {
            case self::PHONE:
                return self::PHONE_STRING;
            case self::TABLET:
                return self::TABLET_STRING;
            default:
                return self::DESKTOP_STRING;
        }
    }
}