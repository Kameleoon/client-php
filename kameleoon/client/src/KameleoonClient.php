<?php
namespace Kameleoon;

interface KameleoonClient
{
    const DEFAULT_TIMEOUT_MILLISECONDS = 5000;

    public function addData($visitorCode, ...$data);

    public function flush($visitorCode = NULL);

    public function trackConversion($visitorCode, int $goalID, $revenue = 0.0);

    public function triggerExperiment(
        $visitorCode,
        $experimentID,
        $timeOut = KameleoonClient::DEFAULT_TIMEOUT_MILLISECONDS
    );

    /**
     * @deprecated deprecated since version 3.0.0. Please use `isFeatureActive`
     */
    public function activateFeature(
        string $visitorCode,
        string $featureKey,
        int $timeOut = KameleoonClient::DEFAULT_TIMEOUT_MILLISECONDS
    ): bool;

    /**
     * @deprecated deprecated since version 3.0.0. Please use `getVisitorCode`
     */
    public function obtainVisitorCode($topLevelDomain, $visitorCode = NULL);
    public function getVisitorCode($topLevelDomain, $visitorCode = NULL);

    /**
     * @deprecated deprecated since version 3.0.0. Please use `isFeatureActive`
     */
    public function obtainVariationAssociatedData($variationId);
    public function getVariationAssociatedData($variationId);

    public function retrieveDataFromRemoteSource(
        string $key,
        int $timeOut = KameleoonClient::DEFAULT_TIMEOUT_MILLISECONDS
    );

    public function getFeatureVariationKey(
        string $visitorCode,
        string $featureKey,
        int $timeOut = KameleoonClient::DEFAULT_TIMEOUT_MILLISECONDS
    );

    public function getFeatureVariable(
        string $visitorCode,
        string $featureKey,
        string $variableName,
        int $timeOut = self::DEFAULT_TIMEOUT_MILLISECONDS
    ): array|object|string|float|int|bool|null;

    public function isFeatureActive(
        string $visitorCode,
        string $featureKey,
        int $timeOut = self::DEFAULT_TIMEOUT_MILLISECONDS
    ): bool;

    public function getFeatureAllVariables(string $featureKey, string $variationKey): array;

    public function getExperimentList(): array;

    public function getExperimentListForVisitor(
        string $visitorCode,
        bool $onlyAllocated = true
    ): array;

    public function getFeatureList(): array;

    public function getActiveFeatureListForVisitor(string $visitorCode): array;
}
