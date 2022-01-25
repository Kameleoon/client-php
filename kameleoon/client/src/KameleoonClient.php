<?php
namespace Kameleoon;

interface KameleoonClient
{
    public function addData($visitorCode, ...$data);
    public function flush($visitorCode = NULL);
    public function trackConversion($visitorCode, $goalID, $revenue = 0.0);
    public function triggerExperiment($visitorCode, $experimentID, $timeOut = 2000);
    public function activateFeature($visitorCode, $featureIdOrName, $timeOut = 2000);
    public function obtainVisitorCode($topLevelDomain, $visitorCode = NULL);
    public function obtainVariationAssociatedData($variationId);
    public function obtainFeatureVariable($featureIdOrName, $variableName);
}