<?php

namespace Kameleoon\Configuration;

class VariationByExposition
{
    public $variationKey;
    public $variationId;
    public $exposition;

    public function __construct($variationByExposition)
    {
        $this->variationKey = $variationByExposition->variationKey;
        $this->variationId = $variationByExposition->variationId;
        $this->exposition = $variationByExposition->exposition;
    }
}
?>
