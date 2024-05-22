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
use Kameleoon\Data\VisitorVisits;

class VisitorImpl implements Visitor
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
    private ?VisitorVisits $visitorVisits;
    private ?string $userAgent;
    private bool $legalConsent;
    private ?string $mappingIdentifier;

    public function addData(bool $overwrite, BaseData ...$data): void
    {
        foreach ($data as $d) {
            $this->processDataType($d, $overwrite);
        }
    }

    public function getUnsentData(): Generator
    {
        foreach ($this->getData() as $data) {
            if (!$data->isSent()) {
                yield $data;
            }
        }
    }

    public function getData(): Generator
    {
        if (isset($this->mapCustomData)) {
            foreach ($this->mapCustomData as $customData) {
                yield $customData;
            }
        }
        foreach ($this->getPageView() as $pageView) {
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

    public function getPageViewVisit(): array
    {
        return $this->mapPageView ?? [];
    }

    public function getPageView(): Generator
    {
        if (isset($this->mapPageView)) {
            foreach ($this->mapPageView as $visit) {
                yield $visit->getPageView();
            }
        }
    }

    public function getConversion(): array
    {
        return $this->collectionConversion ?? [];
    }

    public function getUnsentConversion(): Generator
    {
        foreach ($this->getConversion() as $conversion) {
            if (!$conversion->isSent()) {
                yield $conversion;
            }
        }
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

    public function assignVariation(
        int $experimentId,
        int $variationId,
        int $ruleType = AssignedVariation::RULE_TYPE_UNKNOWN): void
    {
        $this->addVariation(new AssignedVariationImpl($experimentId, $variationId, $ruleType), true);
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

    private function processDataType(BaseData $data, bool $overwrite): void
    {
        switch (true) {
            case $data instanceof CustomData:
                $this->addCustomData($data, $overwrite);
                break;
            case $data instanceof PageView:
                $this->addPageView($data);
                break;
            case $data instanceof PageViewVisit:
                $this->addPageViewVisit($data);
                break;
            case $data instanceof Device:
                $this->setDevice($data, $overwrite);
                break;
            case $data instanceof Browser:
                $this->setBrowser($data, $overwrite);
                break;
            case $data instanceof Cookie:
                $this->setCookie($data);
                break;
            case $data instanceof OperatingSystem:
                $this->setOperatingSystem($data, $overwrite);
                break;
            case $data instanceof Geolocation:
                $this->setGeolocation($data, $overwrite);
                break;
            case $data instanceof VisitorVisits:
                $this->setVisitorVisits($data);
                break;
            case $data instanceof Conversion:
                $this->addConversion($data);
                break;
            case $data instanceof UserAgent:
                $this->setUserAgent($data);
                break;
            case $data instanceof AssignedVariation:
                $this->addVariation($data, $overwrite);
                break;
            default:
                break;
        }
    }

    private function &getOrCreateMapCustomData(): array
    {
        if (!isset($this->mapCustomData)) {
            $this->mapCustomData = array();
        }
        return $this->mapCustomData;
    }

    private function addCustomData(CustomData $customData, bool $overwrite): void
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

    private function addPageView(PageView $pageView): void
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

    private function addPageViewVisit(PageViewVisit $pageViewVisit): void
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

    private function setDevice(Device $device, bool $overwrite): void
    {
        if ($overwrite || (($this->device ?? null) === null)) {
            $this->device = $device;
        }
    }

    private function setBrowser(Browser $browser, bool $overwrite): void
    {
        if ($overwrite || (($this->browser ?? null) === null)) {
            $this->browser = $browser;
        }
    }

    private function setCookie(Cookie $cookie): void
    {
        $this->cookie = $cookie;
    }

    private function setOperatingSystem(OperatingSystem $operatingSystem, bool $overwrite): void
    {
        if ($overwrite || (($this->operatingSystem ?? null) === null)) {
            $this->operatingSystem = $operatingSystem;
        }
    }

    private function setGeolocation(Geolocation $geolocation, bool $overwrite): void
    {
        if ($overwrite || (($this->geolocation ?? null) === null)) {
            $this->geolocation = $geolocation;
        }
    }

    private function setVisitorVisits(VisitorVisits $visitorVisits): void
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

    private function addConversion(Conversion $conversion): void
    {
        $this->getOrCreateCollectionConversion()[] = $conversion;
    }

    private function setUserAgent(UserAgent $userAgent): void
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

    private function addVariation(AssignedVariation $variation, bool $overwrite = true): void
    {
        if ($overwrite || !array_key_exists($variation->getExperimentId(), $this->getOrCreateMapAssignedVariation())) {
            $this->getOrCreateMapAssignedVariation()[$variation->getExperimentId()] = $variation;
        }
    }
}
