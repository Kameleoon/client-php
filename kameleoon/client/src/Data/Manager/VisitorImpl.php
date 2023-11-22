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

class VisitorImpl implements Visitor
{
    private array $mapCustomData;
    private array $mapPageView;
    private array $collectionConversion;
    private array $mapAssignedVariation;
    private ?Device $device;
    private ?Browser $browser;
    private ?string $userAgent;
    private bool $legalConsent;

    public function addData(BaseData ...$data): void
    {
        foreach ($data as $d) {
            $this->processDataType($d);
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
            foreach ($this->mapPageView as $pair) {
                yield $pair[0];
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
        int $ruleType = AssignedVariation::RULE_TYPE_UNKNOWN
    ): void {
        $this->getOrCreateMapAssignedVariation()[$experimentId] =
            new AssignedVariationImpl($experimentId, $variationId, $ruleType);
    }

    public function setLegalConsent(bool $legalConsent)
    {
        $this->legalConsent = $legalConsent;
    }

    public function getAssignedVariations(): array
    {
        return $this->mapAssignedVariation ?? [];
    }

    private function processDataType(BaseData $data): void
    {
        switch (true) {
            case $data instanceof CustomData:
                $this->addCustomData($data);
                break;
            case $data instanceof PageView:
                $this->addPageView($data);
                break;
            case $data instanceof Device:
                $this->setDevice($data);
                break;
            case $data instanceof Browser:
                $this->setBrowser($data);
                break;
            case $data instanceof Conversion:
                $this->addConversion($data);
                break;
            case $data instanceof UserAgent:
                $this->setUserAgent($data);
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

    private function addCustomData(CustomData $customData): void
    {
        $this->getOrCreateMapCustomData()[$customData->getId()] = $customData;
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
        if (array_key_exists($url, $mapPageView)) {
            $mapPageView[$url] = [$pageView, $mapPageView[$url][1] + 1];
        } else {
            $mapPageView[$url] = [$pageView, 1];
        }
    }

    private function setDevice(Device $device): void
    {
        $this->device = $device;
    }

    private function setBrowser(Browser $browser): void
    {
        $this->browser = $browser;
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
}
