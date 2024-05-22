<?php

declare(strict_types=1);

namespace Kameleoon\Data\Manager;

use Generator;
use Kameleoon\Data\BaseData;
use Kameleoon\Data\Data;
use Kameleoon\Data\Device;
use Kameleoon\Data\Browser;
use Kameleoon\Data\Cookie;
use Kameleoon\Data\OperatingSystem;
use Kameleoon\Data\Geolocation;
use Kameleoon\Data\VisitorVisits;

interface Visitor
{
    /**
     * Adds data to the visitor's storage.
     *
     * @param bool $overwrite Data addition mode.
     * @param BaseData ...$data An array of Data objects to be added to the visitor's storage.
     */
    public function addData(bool $overwrite, BaseData ...$data): void;

    /**
     * Retrieves all data associated with the visitor, which is required for targeting evaluation.
     *
     * @return Generator A list containing all the data associated with the visitor.
     */
    public function getData(): Generator;

    /**
     * Retrieves all unsent data associated with the visitor, which is required for sending tracking
     * requests.
     *
     * @return Generator A list containing unsent data associated with the visitor.
     */
    public function getUnsentData(): Generator;

    /**
     * Retrieves specific targeting data for the visitor, which is necessary for targeting conditions.
     *
     * @return array A map of integer keys to CustomData values, representing specific targeting data.
     */
    public function getCustomData(): array;

    /**
     * Retrieves a mapping of page view visit information.
     *
     * @return array A map where the keys are strings and the values are pairs consisting of a PageView object
     *         and an integer.
     */
    public function getPageViewVisit(): array;

    /**
     * Retrieves a collection of PageView objects representing the visitor's page views.
     *
     * @return Generator A collection of PageView objects.
     */
    public function getPageView(): Generator;

    /**
     * Retrieves a collection of Conversion objects representing visitor conversions.
     *
     * @return array A collection of Conversion objects.
     */
    public function getConversion(): array;

    /**
     * Retrieves a collection of unsent Conversion objects representing visitor conversions that have
     * not yet been dispatched or reported.
     *
     * @return Generator A collection of unsent Conversion objects.
     */
    public function getUnsentConversion(): Generator;

    /**
     * Retrieves information about the device used by the visitor, if available.
     *
     * @return Device|null A Device object if device information is available, otherwise null.
     */
    public function getDevice(): ?Device;

    /**
     * Retrieves information about the browser used by the visitor, if available.
     *
     * @return Browser|null A Browser object if browser information is available, otherwise null.
     */
    public function getBrowser(): ?Browser;

    /**
     * Retrieves information about the cookies used by the visitor, if available.
     *
     * @return Cookie|null A Cookie object if cookie information is available, otherwise null.
     */
    public function getCookie(): ?Cookie;

    /**
     * Retrieves information about the operating system used by the visitor, if available.
     *
     * @return OperatingSystem|null A OperatingSystem object if the information is available, otherwise null.
     */
    public function getOperatingSystem(): ?OperatingSystem;

    /**
     * Retrieves information about the geolocation of the visitor, if available.
     *
     * @return Geolocation|null A Geolocation object if the information is available, otherwise null.
     */
    public function getGeolocation(): ?Geolocation;

    /**
     * Retrieves information about the visits of the visitor, if available.
     *
     * @return VisitorVisits|null A VisitorVisits object if the information is available, otherwise null.
     */
    public function getVisitorVisits(): ?VisitorVisits;

    /**
     * Retrieves user agent information associated with the visitor.
     *
     * @return string|null A string representing user agent information, or null if not available.
     */
    public function getUserAgent(): ?string;

    /**
     * This method allows the Visitor to associate an experiment with a particular variation ID,
     * indicating that the Visitor has been exposed to that specific variation within the experiment.
     *
     * @param int $experimentId The ID of the experiment to which the variation is assigned.
     * @param int $variationId The ID of the variation being assigned to the experiment.
     * @param int $ruleType Rule type of assigned variation.
     */
    public function assignVariation(int $experimentId, int $variationId, int $ruleType): void;

    /**
     * Retrieves a map of experiment IDs to the corresponding assigned variation for this Visitor.
     *
     * @return array A map where the keys are experiment IDs and the values are assigned variation IDs for
     *         each experiment that this Visitor has been exposed to.
     */
    public function getAssignedVariations(): array;

    /**
     * Gets the legal consent status for the visitor. This status is related to the method
     * `enableLegalConsent`. (See:
     * https://developers.kameleoon.com/apis/activation-api-js/api-reference/#enablelegalconsent)
     *
     * @return bool True if legal consent is enabled for the visitor, false otherwise.
     */
    public function getLegalConsent(): bool;

    /**
     * Sets or removes the legal consent status of a visitor.
     *
     * This method allows you to update the legal consent status for a visitor by providing a boolean
     * value. Setting the legal consent to 'true' indicates that the visitor has given their legal
     * consent, while setting it to 'false' indicates that the visitor has withdrawn or not provided
     * their consent.
     *
     * @param bool $legalConsent A boolean value representing the legal consent status of the visitor. -
     *        'true' indicates that the visitor has given legal consent. - 'false' indicates that the
     *        visitor has withdrawn or not provided legal consent.
     */
    public function setLegalConsent(bool $legalConsent);

    /**
     * Gets the mapping identifier of the visitor.
     *
     * @return ?string
     */
    public function getMappingIdentifier(): ?string;

    /**
     * Sets or removes the mapping identifier of the visitor.
     *
     * @param ?string $value
     */
    public function setMappingIdentifier(?string $value): void;
}
