<?php
namespace Kameleoon\Data;

use Kameleoon\KameleoonClientImpl;
use Kameleoon\Helpers\URLEncoding;

class CustomData implements DataInterface
{
    private $id;
    private array $values;
    private $nonce;

    public function __construct(int $id, string...$values)
    {
        $this->id = $id;
        $this->values = $values;
        $this->nonce = KameleoonClientImpl::obtainNonce();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function obtainFullPostTextLine(): string
    {
        if (count($this->values) == 0) {
            return "";
        }
        $arrayBuilder = array();
        foreach ($this->values as $val) {
            $arrayBuilder[] = array($val, 1);
        }
        $encoded = URLEncoding::encodeURIComponent(
            json_encode($arrayBuilder, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
        $encoded = str_replace("%5C", "", $encoded);
        return
            "eventType=customData&index=" . $this->id .
            "&valueToCount=" . $encoded .
            "&overwrite=true&nonce=" . $this->nonce;
    }
}
