<?php
namespace Kameleoon\Data;

use Kameleoon\KameleoonClientImpl;

class Conversion implements DataInterface
{
    private $goalId;
    private $revenue;
    private $negative;
    private $nonce;

    public function __construct(int $goalId, float $revenue = 0, bool $negative = false) {
        $this->goalId = $goalId;
        $this->revenue = $revenue;
        $this->negative = $negative ? "true" : "false";
        $this->nonce = KameleoonClientImpl::obtainNonce();
    }

    public function obtainFullPostTextLine ()
    {
        return "eventType=conversion&goalId=" . $this->goalId . "&revenue=" . $this->revenue . "&negative=" . $this->negative . "&nonce=" . $this->nonce;
    }
}