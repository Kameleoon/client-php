<?php
namespace Kameleoon\Data;

use Kameleoon\KameleoonClientImpl;

class Conversion implements DataInterface
{
    private $goalId;
    private $revenue;
    private $negative;
    private $nonce;

    public function __construct($goalId, $revenue = NULL, $negative = NULL) {
        $this->goalId = $goalId;
        $this->revenue = $revenue == null ? 0 : $revenue;
        $this->negative = $negative == null ? false : $negative;
        $this->nonce = KameleoonClientImpl::obtainNonce();
    }

    public function obtainFullPostTextLine ()
    {
        return "eventType=conversion&goalId=" . $this->goalId . "&revenue=" . $this->revenue . "&negative=" . $this->negative . "&nonce=" . $this->nonce;
    }
}