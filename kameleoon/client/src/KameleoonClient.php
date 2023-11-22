<?php

namespace Kameleoon;

interface KameleoonClient
{
    public function getVisitorCode(?string $defaultVisitorCode = null, ?int $timeout = null);

    public function addData($visitorCode, ...$data);

    public function flush($visitorCode = null, ?int $timeout = null);

    public function trackConversion($visitorCode, int $goalID, $revenue = 0.0, ?int $timeout = null);

    public function isFeatureActive(string $visitorCode, string $featureKey, ?int $timeout = null): bool;

    public function getFeatureVariationKey(string $visitorCode, string $featureKey, ?int $timeout = null);

    public function getFeatureVariable(
        string $visitorCode,
        string $featureKey,
        string $variableName,
        ?int $timeout = null
    );

    public function getFeatureVariationVariables(string $featureKey, string $variationKey, ?int $timeout = null): array;

    public function getFeatureList(?int $timeout = null): array;

    public function getActiveFeatureListForVisitor(string $visitorCode, ?int $timeout = null): array;

    public function getEngineTrackingCode(string $visitorCode): string;

    public function getRemoteData(string $key, ?int $timeout = null);

    public function getRemoteVisitorData(string $visitorCode, ?int $timeout = null, bool $addData = true): array;

    /**
     * Sets or updates the legal consent status for a visitor identified by their unique visitor code,
     * without affecting values in the response cookies based on the consent status.
     *
     * This method allows you to set or update the legal consent status for a specific visitor
     * identified by their visitor code and adjust values in the response cookies accordingly. The legal
     * consent status is represented by a boolean value, where 'true' indicates consent, and 'false'
     * indicates a withdrawal or absence of consent.
     *
     * Usage example:
     *
     * ```
     * // Set legal consent for a specific visitor and adjust cookie values accordingly
     * $kameeloonClient->setLegalConsent("visitor123", true);
     *
     * // Update legal consent for another visitor and modify cookie values based on the consent
     * // status
     * $kameeloonClient->setLegalConsent("visitor456", false);
     * ```
     *
     * @param string $visitorCode The unique visitor code identifying the visitor.
     * @param bool $legalConsent A boolean value representing the legal consent status.
     *                           - 'true' indicates the visitor has given legal consent.
     *                           - 'false' indicates the visitor has withdrawn or not provided legal consent.
     * @throws Exception\VisitorCodeInvalid Throws when the provided visitor code is not valid (empty, or longer
     *                            than 255 characters)
     */
    public function setLegalConsent(string $visitorCode, bool $legalConsent): void;
}
