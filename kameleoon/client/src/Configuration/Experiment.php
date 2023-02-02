<?php

namespace Kameleoon\Configuration;

class Experiment extends TargetingObject
{
    public $id;
    public $variationConfigurations;
    public $targetingSegment;
    public $isSiteCodeEnabled;

    public function __construct($experiment)
    {
        parent::__construct($experiment);
        $this->id = $experiment->id;
        $this->variationConfigurations = array();
        $this->targetingSegment = null;

        $sortedDeviations = $experiment->deviations;
        usort($sortedDeviations, function (object $first, object $second) { return intval($first->variationId) <=> intval($second->variationId); });
        foreach ($sortedDeviations as $deviation) {
            $variationId = $deviation->variationId == "origin" ? 0 : intval($deviation->variationId);
            $deviation = floatval($deviation->value);
            $respoolTime = null;
            if (isset($experiment->respoolTime)) {
                foreach ($experiment->respoolTime as $rt) {
                    if ($rt->variationId == $variationId || ($variationId == 0 && $rt->variationId == "origin")) {
                        $respoolTime = floatval($rt->value);
                        break;
                    }
                }
            }
            $customJson = '{}';
            foreach ($experiment->variations as $variation) {
                if ($variation->id == $variationId || ($variationId == 0 && $variation->id == "origin")) {
                    $customJson = $variation->customJson;
                    break;
                }
            }
            $this->variationConfigurations[$variationId] = new VariationConfiguration($deviation, $respoolTime, $customJson);
        }

        $this->isSiteCodeEnabled = $experiment->siteEnabled;
    }
}

?>
