<?php

namespace Kameleoon\Configuration;

use Exception;

class VariationConfiguration
{
    public $deviation;
    public $respoolTime;
    public $customJson;

    public function __construct($deviation, $respoolTime, $customJson)
    {
        $this->deviation = $deviation;
        $this->respoolTime = $respoolTime;
        try {
            $this->customJson = json_decode($customJson);
        } catch (Exception $e) {
            $this->customJson = null;
        }
    }
}
?>
