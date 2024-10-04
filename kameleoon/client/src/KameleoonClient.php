<?php

namespace Kameleoon;

use Kameleoon\Data\CustomData;
use Kameleoon\Types\RemoteVisitorDataFilter;

interface KameleoonClient
{
    public function getVisitorCode(?string $defaultVisitorCode = null, ?int $timeout = null);

    public function addData($visitorCode, ...$data);

    public function flush($visitorCode = null, ?int $timeout = null, ?bool $isUniqueIdentifier = null);

    public function trackConversion($visitorCode, int $goalID, $revenue = 0.0, ?int $timeout = null,
        ?bool $isUniqueIdentifier = null);

    public function isFeatureActive(string $visitorCode, string $featureKey, ?int $timeout = null,
        ?bool $isUniqueIdentifier = null): bool;

    public function getFeatureVariationKey(string $visitorCode, string $featureKey, ?int $timeout = null,
        ?bool $isUniqueIdentifier = null);

    public function getFeatureVariable(
        string $visitorCode,
        string $featureKey,
        string $variableName,
        ?int $timeout = null,
        ?bool $isUniqueIdentifier = null
    );

    public function getFeatureVariationVariables(string $featureKey, string $variationKey, ?int $timeout = null): array;

    public function getFeatureList(?int $timeout = null): array;

    /**
     * @deprecated deprecated since version 4.3.0. Please use `getActiveFeatures`
     */
    public function getActiveFeatureListForVisitor(string $visitorCode, ?int $timeout = null): array;

    public function getActiveFeatures(string $visitorCode, ?int $timeout = null): array;

    public function getEngineTrackingCode(string $visitorCode): string;

    public function getRemoteData(string $key, ?int $timeout = null);

    public function getRemoteVisitorData(string $visitorCode, ?int $timeout = null, bool $addData = true,
        ?RemoteVisitorDataFilter $filter = null, ?bool $isUniqueIdentifier = null): array;

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

    /**
     * Retrieves data associated with a visitor's warehouse audiences and adds it to the visitor.
     *
     * Retrieves all audience data associated with the visitor in your data warehouse using the
     * specified `$visitorCode` and `$warehouseKey`. The `$warehouseKey` is typically your internal user
     * ID. The `$customDataIndex` parameter corresponds to the Kameleoon custom data that Kameleoon uses
     * to target your visitors. You can refer to the warehouse targeting documentation for additional details.
     * The method returns a CustomData object, confirming that the data has been added to the visitor
     * and is available for targeting purposes.
     *
     * @param string $visitorCode The unique identifier of the visitor for whom you want to retrieve and add the data.
     * @param int $customDataIndex An integer representing the index of the custom data you want to use to target your
     * BigQuery Audiences.
     * @param ?string $warehouseKey The key to identify the warehouse data, typically your internal user ID.
     * The value is optional.
     * @param ?int $timeout This parameter specifies the maximum amount of time the method can block to wait for a
     * result. This field is optional.
     * @return ?CustomData CustomData is not null in case if it was sucessfully added to the visitor. Otherwise, null.
     * @throws Exception\VisitorCodeInvalid Throws when the provided visitor code is not valid (empty, or longer
     *                            than 255 characters)
     */
    public function getVisitorWarehouseAudience(
        string $visitorCode,
        int $customDataIndex,
        ?string $warehouseKey = null,
        ?int $timeout = null
    ): ?CustomData;
}
