<?php
namespace Kameleoon\Storage;

class VariationStorage
{
    private $savedVariations = array();

    public function getSavedVariation($visitorCode, $experiment) {
        //check if variation is saved
        if (!isset($this->savedVariations[$visitorCode]) || 
            !isset($this->savedVariations[$visitorCode][$experiment->id])) {
            return null;
        }
        $savedVariationId = $this->savedVariations[$visitorCode][$experiment->id][0];
        $assignmentTime = $this->savedVariations[$visitorCode][$experiment->id][1];
        //check assignmentTime is later than respoolTime, else need to reassignment visitor
        foreach ($experiment->variationConfigurations as $variationId => $variationConfiguration) {
            if ($variationId == $savedVariationId) {
                if ($variationConfiguration->respoolTime != null && $variationConfiguration->respoolTime > $assignmentTime) {
                    return null;
                }
            } else {
                return $savedVariationId;
            }
        }
    }

    public function saveVariation($visitorCode, $experimentID, $variationId) {
        $this->savedVariations[$visitorCode][$experimentID] = [$variationId, time()];
    }

    public function getSavedVariations($visitorCode) {
        if (!isset($this->savedVariations[$visitorCode])) {
            return null;
        } else {
            return array_combine(
                array_keys($this->savedVariations[$visitorCode]),
                array_map(function ($variation) { return $variation[0];}, $this->savedVariations[$visitorCode]));
        }

    }
}
