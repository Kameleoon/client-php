<?php

declare(strict_types=1);

namespace Kameleoon\Data\Manager;

use Generator;
use Kameleoon\Data\BaseData;
use Kameleoon\Data\Browser;
use Kameleoon\Data\Conversion;
use Kameleoon\Data\CustomData;
use Kameleoon\Data\Data;
use Kameleoon\Data\Device;
use Kameleoon\Data\PageView;
use Kameleoon\Data\UserAgent;
use Kameleoon\Data\Cookie;
use Kameleoon\Data\OperatingSystem;
use Kameleoon\Data\Geolocation;
use Kameleoon\Data\KcsHeat;
use Kameleoon\Data\VisitorVisits;
use Kameleoon\Data\UniqueIdentifier;
use Kameleoon\Logging\KameleoonLogger;

class VisitorImpl implements Visitor
{
    private VisitorData $data;
    private bool $uniqueIdentifier;

    public function __construct(?VisitorImpl $source = null)
    {
        if ($source == null) {
            $this->data = new VisitorData();
            $this->uniqueIdentifier = false;
        } else {
            $this->data = $source->data;
            $this->uniqueIdentifier = $source->uniqueIdentifier;
        }
    }

    public function addData(bool $overwrite, BaseData ...$data): void
    {
        KameleoonLogger::debug("CALL: Visitor->addData(overwrite: %s, data: %s)", $overwrite, $data);
        foreach ($data as $d) {
            $this->processDataType($d, $overwrite);
        }
        KameleoonLogger::debug("RETURN: Visitor->addData(overwrite: %s, data: %s)", $overwrite, $data);
    }

    public function getUnsentData(): Generator
    {
        KameleoonLogger::debug("CALL: Visitor->getUnsentData()");
        foreach ($this->data->getData() as $data) {
            if (!$data->isSent()) {
                yield $data;
            }
        }
        KameleoonLogger::debug("RETURN: Visitor->getUnsentData() yield (data)");
    }

    public function getData(): Generator
    {
        KameleoonLogger::debug("CALL: Visitor->getData()");
        $data = $this->data->getData();
        KameleoonLogger::debug("RETURN: Visitor->getData() yield (data)");
        return $data;
    }

    public function getCustomData(): array
    {
        $customData = $this->data->getCustomData();
        KameleoonLogger::debug("CALL/RETURN: Visitor->getCustomData() -> (customData: %s)", $customData);
        return $customData;
    }

    public function getPageViewVisits(): array
    {
        $pageViewVisits = $this->data->getPageViewVisits();
        KameleoonLogger::debug("CALL/RETURN: Visitor->getPageViewVisits() -> (pageViewVisits: %s)", $pageViewVisits);
        return $pageViewVisits;
    }

    public function getPageViews(): Generator
    {
        KameleoonLogger::debug("CALL: Visitor->getPageViews()");
        $pageViews = $this->data->getPageViews();
        KameleoonLogger::debug("RETURN: Visitor->getPageViews() yield (pageViews)");
        return $pageViews;
    }

    public function getConversions(): array
    {
        $conversions = $this->data->getConversions();
        KameleoonLogger::debug("CALL/RETURN: Visitor->getConversions() -> (conversions: %s)", $conversions);
        return $conversions;
    }

    public function getUnsentConversions(): Generator
    {
        KameleoonLogger::debug("CALL: Visitor->getUnsentConversions()");
        foreach ($this->data->getConversions() as $conversion) {
            if (!$conversion->isSent()) {
                yield $conversion;
            }
        }
        KameleoonLogger::debug("RETURN: Visitor->getUnsentConversions() yield (conversion)");
    }

    public function getDevice(): ?Device
    {
        $device = $this->data->getDevice();
        KameleoonLogger::debug("CALL/RETURN: Visitor->getDevice() -> (device: %s)", $device);
        return $device;
    }

    public function getBrowser(): ?Browser
    {
        $browser = $this->data->getBrowser();
        KameleoonLogger::debug("CALL/RETURN: Visitor->getBrowser() -> (browser: %s)", $browser);
        return $browser;
    }

    public function getCookie(): ?Cookie
    {
        $cookie = $this->data->getCookie();
        KameleoonLogger::debug("CALL/RETURN: Visitor->getCookie() -> (cookie: %s)", $cookie);
        return $cookie;
    }

    public function getOperatingSystem(): ?OperatingSystem
    {
        $operatingSystem = $this->data->getOperatingSystem();
        KameleoonLogger::debug("CALL/RETURN: Visitor->getOperatingSystem() -> (operatingSystem: %s)", $operatingSystem);
        return $operatingSystem;
    }

    public function getGeolocation(): ?Geolocation
    {
        $geolocation = $this->data->getGeolocation();
        KameleoonLogger::debug("CALL/RETURN: Visitor->getGeolocation() -> (geolocation: %s)", $geolocation);
        return $geolocation;
    }

    public function getKcsHeat(): ?KcsHeat
    {
        $kcsHeat = $this->data->getKcsHeat();
        KameleoonLogger::debug("CALL/RETURN: Visitor->getKcsHeat() -> (kcsHeat: %s)", $kcsHeat);
        return $kcsHeat;
    }

    public function getVisitorVisits(): ?VisitorVisits
    {
        $visitorVisits = $this->data->getVisitorVisits();
        KameleoonLogger::debug("CALL/RETURN: Visitor->getVisitorVisits() -> (visitorVisits: %s)", $visitorVisits);
        return $visitorVisits;
    }

    public function getUserAgent(): ?string
    {
        $userAgent = $this->data->getUserAgent();
        KameleoonLogger::debug("CALL/RETURN: Visitor->getUserAgent() -> (userAgent: '%s')", $userAgent);
        return $userAgent;
    }

    public function getLegalConsent(): bool
    {
        $legalConsent = $this->data->getLegalConsent();
        KameleoonLogger::debug("CALL/RETURN: Visitor->getLegalConsent() -> (legalConsent: %s)", $legalConsent);
        return $legalConsent;
    }

    public function assignVariation(
        int $experimentId,
        int $variationId,
        int $ruleType = AssignedVariation::RULE_TYPE_UNKNOWN): void
    {
        $this->data->addVariation(new AssignedVariation($experimentId, $variationId, $ruleType), true);
    }

    public function setLegalConsent(bool $legalConsent): void
    {
        $this->data->setLegalConsent($legalConsent);
    }

    public function getMappingIdentifier(): ?string
    {
        return $this->data->getMappingIdentifier();
    }

    public function setMappingIdentifier(?string $value): void
    {
        $this->data->setMappingIdentifier($value);
    }

    public function getAssignedVariations(): array
    {
        return $this->data->getAssignedVariations();
    }

    public function isUniqueIdentifier(): bool
    {
        return $this->uniqueIdentifier;
    }

    private function processDataType(BaseData $data, bool $overwrite): void
    {
        switch (true) {
            case $data instanceof CustomData:
                $this->data->addCustomData($data, $overwrite);
                break;
            case $data instanceof PageView:
                $this->data->addPageView($data);
                break;
            case $data instanceof PageViewVisit:
                $this->data->addPageViewVisit($data);
                break;
            case $data instanceof Device:
                $this->data->setDevice($data, $overwrite);
                break;
            case $data instanceof Browser:
                $this->data->setBrowser($data, $overwrite);
                break;
            case $data instanceof Cookie:
                $this->data->setCookie($data);
                break;
            case $data instanceof OperatingSystem:
                $this->data->setOperatingSystem($data, $overwrite);
                break;
            case $data instanceof Geolocation:
                $this->data->setGeolocation($data, $overwrite);
                break;
            case $data instanceof KcsHeat:
                $this->data->setKcsHeat($data);
                break;
            case $data instanceof VisitorVisits:
                $this->data->setVisitorVisits($data);
                break;
            case $data instanceof Conversion:
                $this->data->addConversion($data);
                break;
            case $data instanceof UserAgent:
                $this->data->setUserAgent($data);
                break;
            case $data instanceof AssignedVariation:
                $this->data->addVariation($data, $overwrite);
                break;
            case $data instanceof UniqueIdentifier:
                $this->uniqueIdentifier = $data->getValue();
                break;
            default:
                KameleoonLogger::warning("Added data has unsupported type " . get_class($data));
                break;
        }
    }

    public function clone(): Visitor
    {
        return new VisitorImpl($this);
    }
}


class VisitorData
{
    private array $mapCustomData;
    private array $mapPageView;
    private array $collectionConversion;
    private array $mapAssignedVariation;
    private ?Device $device;
    private ?Browser $browser;
    private ?Cookie $cookie;
    private ?OperatingSystem $operatingSystem;
    private ?Geolocation $geolocation;
    private ?KcsHeat $kcsHeat;
    private ?VisitorVisits $visitorVisits;
    private ?string $userAgent;
    private bool $legalConsent;
    private ?string $mappingIdentifier;

    public function getData(): Generator
    {
        if (isset($this->mapCustomData)) {
            foreach ($this->mapCustomData as $customData) {
                yield $customData;
            }
        }
        foreach ($this->getPageViews() as $pageView) {
            yield $pageView;
        }
        if (isset($this->collectionConversion)) {
            foreach ($this->collectionConversion as $conversion) {
                yield $conversion;
            }
        }
        if (isset($this->device)) {
            yield $this->device;
        }
        if (isset($this->browser)) {
            yield $this->browser;
        }
        if (isset($this->operatingSystem)) {
            yield $this->operatingSystem;
        }
        if (isset($this->geolocation)) {
            yield $this->geolocation;
        }
        if (isset($this->mapAssignedVariation)) {
            foreach ($this->mapAssignedVariation as $assignedVariation) {
                yield $assignedVariation;
            }
        }
    }

    public function getCustomData(): array
    {
        return $this->mapCustomData ?? [];
    }

    public function getPageViewVisits(): array
    {
        return $this->mapPageView ?? [];
    }

    public function getPageViews(): Generator
    {
        if (isset($this->mapPageView)) {
            foreach ($this->mapPageView as $visit) {
                yield $visit->getPageView();
            }
        }
    }

    public function getConversions(): array
    {
        return $this->collectionConversion ?? [];
    }

    public function getDevice(): ?Device
    {
        return $this->device ?? null;
    }

    public function getBrowser(): ?Browser
    {
        return $this->browser ?? null;
    }

    public function getCookie(): ?Cookie
    {
        return $this->cookie ?? null;
    }

    public function getOperatingSystem(): ?OperatingSystem
    {
        return $this->operatingSystem ?? null;
    }

    public function getGeolocation(): ?Geolocation
    {
        return $this->geolocation ?? null;
    }

    public function getKcsHeat(): ?KcsHeat
    {
        return $this->kcsHeat ?? null;
    }

    public function getVisitorVisits(): ?VisitorVisits
    {
        return $this->visitorVisits ?? null;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent ?? null;
    }

    public function getLegalConsent(): bool
    {
        return $this->legalConsent ?? false;
    }

    public function setLegalConsent(bool $legalConsent): void
    {
        $this->legalConsent = $legalConsent;
    }

    public function getMappingIdentifier(): ?string
    {
        return $this->mappingIdentifier ?? null;
    }

    public function setMappingIdentifier(?string $value): void
    {
        $this->mappingIdentifier = $value;
    }

    public function getAssignedVariations(): array
    {
        return $this->mapAssignedVariation ?? [];
    }

    private function &getOrCreateMapCustomData(): array
    {
        if (!isset($this->mapCustomData)) {
            $this->mapCustomData = array();
        }
        return $this->mapCustomData;
    }

    public function addCustomData(CustomData $customData, bool $overwrite): void
    {
        if ($overwrite || !array_key_exists($customData->getId(), $this->getOrCreateMapCustomData())) {
            $this->getOrCreateMapCustomData()[$customData->getId()] = $customData;
        }
    }

    private function &getOrCreateMapPageView(): array
    {
        if (!isset($this->mapPageView)) {
            $this->mapPageView = array();
        }
        return $this->mapPageView;
    }

    public function addPageView(PageView $pageView): void
    {
        $url = $pageView->getUrl();
        $mapPageView = &$this->getOrCreateMapPageView();
        $visit = $mapPageView[$url] ?? null;
        if ($visit !== null) {
            $mapPageView[$url] = $visit->overwrite($pageView);
        } else {
            $mapPageView[$url] = new PageViewVisit($pageView);
        }
    }

    public function addPageViewVisit(PageViewVisit $pageViewVisit): void
    {
        $url = $pageViewVisit->getPageView()->getUrl();
        $mapPageView = &$this->getOrCreateMapPageView();
        $visit = $mapPageView[$url] ?? null;
        if ($visit !== null) {
            $visit->merge($pageViewVisit);
        } else {
            $mapPageView[$url] = $pageViewVisit;
        }
    }

    public function setDevice(Device $device, bool $overwrite): void
    {
        if ($overwrite || (($this->device ?? null) === null)) {
            $this->device = $device;
        }
    }

    public function setBrowser(Browser $browser, bool $overwrite): void
    {
        if ($overwrite || (($this->browser ?? null) === null)) {
            $this->browser = $browser;
        }
    }

    public function setCookie(Cookie $cookie): void
    {
        $this->cookie = $cookie;
    }

    public function setOperatingSystem(OperatingSystem $operatingSystem, bool $overwrite): void
    {
        if ($overwrite || (($this->operatingSystem ?? null) === null)) {
            $this->operatingSystem = $operatingSystem;
        }
    }

    public function setGeolocation(Geolocation $geolocation, bool $overwrite): void
    {
        if ($overwrite || (($this->geolocation ?? null) === null)) {
            $this->geolocation = $geolocation;
        }
    }

    public function setKcsHeat(KcsHeat $kcsHeat): void
    {
        $this->kcsHeat = $kcsHeat;
    }

    public function setVisitorVisits(VisitorVisits $visitorVisits): void
    {
        $this->visitorVisits = $visitorVisits;
    }

    private function &getOrCreateCollectionConversion(): array
    {
        if (!isset($this->collectionConversion)) {
            $this->collectionConversion = array();
        }
        return $this->collectionConversion;
    }

    public function addConversion(Conversion $conversion): void
    {
        $this->getOrCreateCollectionConversion()[] = $conversion;
    }

    public function setUserAgent(UserAgent $userAgent): void
    {
        $this->userAgent = $userAgent->getValue();
    }

    private function &getOrCreateMapAssignedVariation(): array
    {
        if (!isset($this->mapAssignedVariation)) {
            $this->mapAssignedVariation = array();
        }
        return $this->mapAssignedVariation;
    }

    public function addVariation(AssignedVariation $variation, bool $overwrite = true): void
    {
        if ($overwrite || !array_key_exists($variation->getExperimentId(), $this->getOrCreateMapAssignedVariation())) {
            $this->getOrCreateMapAssignedVariation()[$variation->getExperimentId()] = $variation;
        }
    }
}
