<?php

declare(strict_types=1);

namespace Kameleoon\Data\Manager;

use Generator;
use Kameleoon\Data\BaseData;
use Kameleoon\Data\Browser;
use Kameleoon\Data\CBScores;
use Kameleoon\Data\Conversion;
use Kameleoon\Data\Cookie;
use Kameleoon\Data\CustomData;
use Kameleoon\Data\Data;
use Kameleoon\Data\Device;
use Kameleoon\Data\Geolocation;
use Kameleoon\Data\KcsHeat;
use Kameleoon\Data\OperatingSystem;
use Kameleoon\Data\PageView;
use Kameleoon\Data\Personalization;
use Kameleoon\Data\TargetedSegment;
use Kameleoon\Data\UniqueIdentifier;
use Kameleoon\Data\UserAgent;
use Kameleoon\Data\VisitorVisits;
use Kameleoon\Data\Manager\LegalConsent;
use Kameleoon\Helpers\TimeHelper;
use Kameleoon\Logging\KameleoonLogger;

/** @internal */
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

    public function getTimeStarted(): int
    {
        return $this->data->getTimeStarted();
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

    public function getCBScores(): ?CBScores
    {
        $cbs = $this->data->getCBScores();
        KameleoonLogger::debug("CALL/RETURN: Visitor->getCBScores() -> (cbs: %s)", $cbs);
        return $cbs;
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

    public function getLegalConsent(): int
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

    public function setLegalConsent(int $legalConsent): void
    {
        $this->data->setLegalConsent($legalConsent);
    }

    public function getMappingIdentifier(): ?string
    {
        return $this->data->getMappingIdentifier();
    }

    public function setMappingIdentifier(?string $value): void
    {
        if ($this->data->getMappingIdentifier() === null) {
            $this->data->setMappingIdentifier($value);
        }
    }

    public function getAssignedVariations(): array
    {
        return $this->data->getAssignedVariations();
    }

    public function getPersonalizations(): array
    {
        return $this->data->getPersonalizations();
    }

    public function getTargetedSegments(): array
    {
        return $this->data->getTargetedSegments();
    }

    public function getForcedFeatureVariation(string $featureKey): ?ForcedFeatureVariation
    {
        return $this->data->getForcedFeatureVariation($featureKey);
    }

    public function getForcedExperimentVariation(int $experimentId): ?ForcedExperimentVariation
    {
        return $this->data->getForcedExperimentVariation($experimentId);
    }

    public function resetForcedExperimentVariation(int $experimentId): void
    {
        $this->data->resetForcedExperimentVariation($experimentId);
    }

    public function updateSimulatedVariations(array $variations): void
    {
        $this->data->updateSimulatedVariations($variations);
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
            case $data instanceof CBScores:
                $this->data->setCBScores($data, $overwrite);
                break;
            case $data instanceof VisitorVisits:
                $this->data->setVisitorVisits($data, $overwrite);
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
            case $data instanceof Personalization:
                $this->data->addPersonalization($data, $overwrite);
                break;
            case $data instanceof TargetedSegment:
                $this->data->addTargetedSegment($data);
                break;
            case $data instanceof ForcedFeatureVariation:
                $this->data->addForcedFeatureVariation($data);
                break;
            case $data instanceof ForcedExperimentVariation:
                $this->data->addForcedExperimentVariation($data);
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
    private int $timeStarted;
    private array $mapCustomData;
    private array $mapPageView;
    private array $collectionConversion;
    private array $mapAssignedVariation;
    private array $personalizations;
    private array $targetedSegments;
    private array $forcedVariations;
    private array $simulatedVariations;
    private ?Device $device;
    private ?Browser $browser;
    private ?Cookie $cookie;
    private ?OperatingSystem $operatingSystem;
    private ?Geolocation $geolocation;
    private ?KcsHeat $kcsHeat;
    private ?CBScores $cbscores;
    private ?VisitorVisits $visitorVisits;
    private ?string $userAgent;
    private int $legalConsent;
    private ?string $mappingIdentifier;

    public function __construct()
    {
        $this->timeStarted = TimeHelper::nowInMilliseconds();
    }

    public function getTimeStarted(): int
    {
        return $this->timeStarted;
    }

    public function getData(): Generator
    {
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
        if (isset($this->visitorVisits)) {
            yield $this->visitorVisits;
        }
        if (isset($this->mapCustomData)) {
            foreach ($this->mapCustomData as $customData) {
                yield $customData;
            }
        }
        foreach ($this->getPageViews() as $pageView) {
            yield $pageView;
        }
        if (isset($this->mapAssignedVariation)) {
            foreach ($this->mapAssignedVariation as $assignedVariation) {
                yield $assignedVariation;
            }
        }
        if (isset($this->targetedSegments)) {
            foreach ($this->targetedSegments as $targetedSegment) {
                yield $targetedSegment;
            }
        }
        if (isset($this->collectionConversion)) {
            foreach ($this->collectionConversion as $conversion) {
                yield $conversion;
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

    public function getCBScores(): ?CBScores
    {
        return $this->cbscores ?? null;
    }

    public function getVisitorVisits(): ?VisitorVisits
    {
        return $this->visitorVisits ?? null;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent ?? null;
    }

    public function getLegalConsent(): int
    {
        return $this->legalConsent ?? LegalConsent::UNKNOWN;
    }

    public function setLegalConsent(int $legalConsent): void
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

    public function getPersonalizations(): array
    {
        return $this->personalizations ?? [];
    }

    public function getTargetedSegments(): array
    {
        return $this->targetedSegments ?? [];
    }

    public function getForcedFeatureVariation(string $featureKey): ?ForcedFeatureVariation
    {
        return $this->simulatedVariations[$featureKey] ?? null;
    }

    public function getForcedExperimentVariation(int $experimentId): ?ForcedExperimentVariation
    {
        return $this->forcedVariations[$experimentId] ?? null;
    }

    public function resetForcedExperimentVariation(int $experimentId): void
    {
        unset($this->forcedVariations[$experimentId]);
    }

    public function updateSimulatedVariations(array $variations): void
    {
        if (empty($this->simulatedVariations) && empty($variations)) {
            return;
        }
        $this->simulatedVariations = [];
        foreach ($variations as $variation) {
            $this->simulatedVariations[$variation->getFeatureKey()] = $variation;
        }
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
        if ($overwrite || !array_key_exists($customData->getIndex(), $this->getOrCreateMapCustomData())) {
            $this->getOrCreateMapCustomData()[$customData->getIndex()] = $customData;
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

    public function setCBScores(CBScores $cbs, bool $overwrite): void
    {
        if ($overwrite || (($this->cbscores ?? null) === null)) {
            $this->cbscores = $cbs;
        }
    }

    public function setVisitorVisits(VisitorVisits $visitorVisits, bool $overwrite): void
    {
        if ($overwrite || (($this->visitorVisits ?? null) === null)) {
            $this->visitorVisits = $visitorVisits->localize($this->timeStarted);
        }
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
        $this->mapAssignedVariation ??= array();
        return $this->mapAssignedVariation;
    }

    public function addVariation(AssignedVariation $variation, bool $overwrite = true): void
    {
        if ($overwrite || !array_key_exists($variation->getExperimentId(), $this->getOrCreateMapAssignedVariation())) {
            $this->getOrCreateMapAssignedVariation()[$variation->getExperimentId()] = $variation;
        }
    }

    private function &getOrCreatePersonalizations(): array
    {
        $this->personalizations ??= array();
        return $this->personalizations;
    }

    public function addPersonalization(Personalization $personalization, bool $overwrite = true): void
    {
        if ($overwrite || !array_key_exists($personalization->getId(), $this->getOrCreatePersonalizations())) {
            $this->getOrCreatePersonalizations()[$personalization->getId()] = $personalization;
        }
    }

    private function &getOrCreateTargetedSegments(): array
    {
        $this->targetedSegments ??= array();
        return $this->targetedSegments;
    }

    public function addTargetedSegment(TargetedSegment $targetedSegment): void
    {
        $this->getOrCreateTargetedSegments()[$targetedSegment->getId()] = $targetedSegment;
    }

    public function addForcedFeatureVariation(ForcedFeatureVariation $variation): void
    {
        $this->simulatedVariations ??= [];
        $this->simulatedVariations[$variation->getFeatureKey()] = $variation;
    }

    public function addForcedExperimentVariation(ForcedExperimentVariation $variation): void
    {
        $this->forcedVariations ??= [];
        $this->forcedVariations[$variation->getRule()->experiment->id] = $variation;
    }
}
