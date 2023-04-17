<?php

namespace Kameleoon;

interface KameleoonClient
{
    public function addData($visitorCode, ...$data);

    public function flush($visitorCode = null);

    public function trackConversion($visitorCode, int $goalID, $revenue = 0.0);

    public function triggerExperiment(string $visitorCode, int $experimentID, ?int $timeout);

    /**
     * @deprecated deprecated since version 3.0.0. Please use `isFeatureActive`
     */
    public function activateFeature(string $visitorCode, string $featureKey, ?int $timeout): bool;

    public function isFeatureActive(string $visitorCode, string $featureKey, ?int $timeout): bool;
    /**
     * @deprecated deprecated since version 3.0.0. Please use `getVisitorCode`
     */
    public function obtainVisitorCode($topLevelDomain, $visitorCode = null);
    public function getVisitorCode($topLevelDomain, $visitorCode = null);

    /**
     * @deprecated deprecated since version 3.0.0. Please use `getVariationAssociatedData`
     */
    public function obtainVariationAssociatedData(int $variationId, ?int $timeout);
    public function getVariationAssociatedData(int $variationId, ?int $timeout);

    /**
     * @deprecated deprecated since version 3.1.0. Please use `getRemoteData`
     */
    public function retrieveDataFromRemoteSource(string $key, ?int $timeout);

    public function getRemoteData(string $key, ?int $timeout);

    public function getFeatureVariationKey(string $visitorCode, string $featureKey, ?int $timeout);

    public function getFeatureVariable(
        string $visitorCode,
        string $featureKey,
        string $variableName,
        ?int $timeout
    );

    public function getFeatureAllVariables(string $featureKey, string $variationKey, ?int $timeout): array;

    public function getExperimentList(?int $timeout): array;

    public function getExperimentListForVisitor(
        string $visitorCode,
        bool $onlyAllocated,
        ?int $timeout
    ): array;

    public function getFeatureList(?int $timeout): array;

    public function getActiveFeatureListForVisitor(string $visitorCode, ?int $timeout): array;

    public function getEngineTrackingCode(string $visitorCode): string;
}
