<?php

declare(strict_types=1);

namespace Kameleoon\Managers\RemoteData;

use Kameleoon\Configuration\CustomDataInfo;
use Kameleoon\Data\Browser;
use Kameleoon\Data\Conversion;
use Kameleoon\Data\CustomData;
use Kameleoon\Data\Device;
use Kameleoon\Data\Geolocation;
use Kameleoon\Data\OperatingSystem;
use Kameleoon\Data\PageView;
use Kameleoon\Data\VisitorVisits;
use Kameleoon\Data\Manager\AssignedVariation;
use Kameleoon\Data\Manager\AssignedVariationImpl;
use Kameleoon\Data\Manager\PageViewVisit;

class RemoteVisitorData
{
    public array $customDataDict;
    public array $pageViewVisits;
    public array $conversions;
    public array $experiments;
    public ?Device $device;
    public ?Browser $browser;
    public ?OperatingSystem $operatingSystem;
    public ?Geolocation $geolocation;
    public VisitorVisits $previousVisitorVisits;

    public function __construct($json)
    {
        $this->customDataDict = [];
        $this->pageViewVisits = [];
        $this->conversions = [];
        $this->experiments = [];
        $this->device = null;
        $this->browser = null;
        $this->operatingSystem = null;
        $this->geolocation = null;
        $this->parseVisit(($json !== null) ? $json->currentVisit ?? null : null);
        $this->previousVisitorVisits =
            $this->parsePreviousVisits(($json !== null) ? $json->previousVisits ?? null : null);
    }

    private function parsePreviousVisits($previousVisits): VisitorVisits
    {
        $prevVisitsTimestamps = [];
        if (is_array($previousVisits)) {
            foreach ($previousVisits as $prevVisit) {
                if ($prevVisit != null) {
                    $timeStarted = $prevVisit->timeStarted ?? 0;
                    array_push($prevVisitsTimestamps, $timeStarted);
                    $this->parseVisit($prevVisit);
                }
            }
        }
        return new VisitorVisits($prevVisitsTimestamps);
    }

    private function parseVisit($visit): void
    {
        if ($visit == null) {
            return;
        }
        $this->parseCustomData($visit->customDataEvents ?? null);
        $this->parsePages($visit->pageEvents ?? null);
        $this->parseExperiments($visit->experimentEvents ?? null);
        $this->parseConversions($visit->conversionEvents ?? null);
        $this->parseGeolocation($visit->geolocationEvents ?? null);
        $this->parseStaticData($visit->staticDataEvent ?? null);
    }

    private function parseCustomData($customDataEvents): void
    {
        if (!is_array($customDataEvents)) {
            return;
        }
        for ($i = count($customDataEvents) - 1; $i >= 0; $i--) {
            $customDataEvent = $customDataEvents[$i] ?? null;
            if ($customDataEvent == null) {
                continue;
            }
            $data = $customDataEvent->data ?? null;
            if ($data !== null) {
                $id = $data->index ?? null;
                if (($id !== null) && !array_key_exists($id, $this->customDataDict)) {
                    $valuesCountMap = $data->valuesCountMap ?? null;
                    $values = is_object($valuesCountMap) ? array_keys((array)$valuesCountMap) : [];
                    $cd = new CustomData($id, ...$values);
                    $this->customDataDict[$id] = $cd;
                }
            }
        }
    }

    private function parsePages($pageEvents): void
    {
        if (!is_array($pageEvents)) {
            return;
        }
        for ($i = count($pageEvents) - 1; $i >= 0; $i--) {
            $pageEvent = $pageEvents[$i] ?? null;
            if ($pageEvent == null) {
                continue;
            }
            $data = $pageEvent->data;
            if ($data == null) {
                continue;
            }
            $href = $data->href ?? null;
            if ($href == null) {
                continue;
            }
            $pageViewVisit = $this->pageViewVisits[$href] ?? null;
            if ($pageViewVisit === null) {
                $title = $data->title ?? null;
                $ts = $pageEvent->time ?? 0;
                $pageView = new PageView($href, $title);
                $pageViewVisit = new PageViewVisit($pageView, 1, $ts);
                $this->pageViewVisits[$href] = $pageViewVisit;
            } else {
                $pageViewVisit->increasePageVisits();
            }
        }
    }

    private function parseExperiments($experimentEvents): void
    {
        if (!is_array($experimentEvents)) {
            return;
        }
        for ($i = count($experimentEvents) - 1; $i >= 0; $i--) {
            $experimentEvent = $experimentEvents[$i] ?? null;
            if ($experimentEvent == null) {
                continue;
            }
            $data = $experimentEvent->data ?? null;
            if ($data == null) {
                continue;
            }
            $expId = $data->id ?? 0;
            if (!array_key_exists($expId, $this->experiments)) {
                $varId = $data->variationId ?? 0;
                $ts = $experimentEvent->time ?? 0;
                $variation = new AssignedVariationImpl($expId, $varId, AssignedVariation::RULE_TYPE_UNKNOWN, $ts);
                $this->experiments[$expId] = $variation;
            }
        }
    }

    private function parseConversions($conversionEvents): void
    {
        if (!is_array($conversionEvents)) {
            return;
        }
        for ($i = count($conversionEvents) - 1; $i >= 0; $i--) {
            $conversionEvent = $conversionEvents[$i] ?? null;
            if ($conversionEvent == null) {
                continue;
            }
            $data = $conversionEvent->data ?? null;
            if ($data == null) {
                continue;
            }
            $goalId = $data->goalId ?? 0;
            $revenue = $data->revenue ?? 0.0;
            $negative = $data->negative ?? false;
            $conversion = new Conversion($goalId, $revenue, $negative);
            array_push($this->conversions, $conversion);
        }
    }

    private function parseGeolocation($geolocationEvents): void
    {
        if (!is_array($geolocationEvents) || ($this->geolocation !== null)) {
            return;
        }
        $geolocationEvent = $geolocationEvents[count($geolocationEvents) - 1] ?? null;
        if ($geolocationEvent == null) {
            return;
        }
        $data = $geolocationEvent->data ?? null;
        if ($data == null) {
            return;
        }
        $country = $data->country ?? null;
        $region = $data->region ?? null;
        $city = $data->city ?? null;
        $this->geolocation = new Geolocation($country, $region, $city);
    }

    private function parseStaticData($staticDataEvent): void
    {
        if (($staticDataEvent == null) ||
                (($this->device !== null) && ($this->browser !== null) && ($this->operatingSystem !== null))) {
            return;
        }
        $data = $staticDataEvent->data ?? null;
        if ($data == null) {
            return;
        }
        if ($this->device === null) {
            $deviceType = $data->deviceType ?? null;
            if ($deviceType != null) {
                $this->device = new Device($deviceType);
            }
        }
        if ($this->browser === null) {
            $browserName = $data->browser ?? null;
            $browserType = Browser::$browsers[$browserName] ?? null;
            if ($browserType != null) {
                $browserVersion = $data->browserVersion ?? NAN;
                $this->browser = new Browser($browserType, $browserVersion);
            }
        }
        if ($this->operatingSystem === null) {
            $osType = $data->os ?? null;
            $osIndex = OperatingSystem::$typeIndices[$osType] ?? null;
            if ($osIndex != null) {
                $this->operatingSystem = new OperatingSystem($osIndex);
            }
        }
    }

    public function markVisitorDataAsSent(?CustomDataInfo $customDataInfo): void
    {
        foreach ($this->customDataDict as $id => $customData) {
            if (($customDataInfo == null) || !$customDataInfo->isVisitorScope($id)) {
                $customData->markAsSent();
            }
        }
        foreach ($this->pageViewVisits as $url => $pageViewVisit) {
            $pageViewVisit->getPageView()->markAsSent();
        }
        foreach ($this->conversions as $conversion) {
            $conversion->markAsSent();
        }
        foreach ($this->experiments as $expId => $variation) {
            $variation->markAsSent();
        }
        if ($this->device !== null) {
            $this->device->markAsSent();
        }
        if ($this->browser !== null) {
            $this->browser->markAsSent();
        }
        if ($this->operatingSystem !== null) {
            $this->operatingSystem->markAsSent();
        }
        if ($this->geolocation !== null) {
            $this->geolocation->markAsSent();
        }
    }

    public function collectVisitorDataToReturn(): array
    {
        $dataList = [];
        array_push($dataList, ...array_values($this->customDataDict));
        foreach ($this->pageViewVisits as $url => $pageViewVisit) {
            array_push($dataList, $pageViewVisit->getPageView());
        }
        array_push($dataList, ...$this->conversions);
        if ($this->device !== null) {
            array_push($dataList, $this->device);
        }
        if ($this->browser !== null) {
            array_push($dataList, $this->browser);
        }
        if ($this->operatingSystem !== null) {
            array_push($dataList, $this->operatingSystem);
        }
        if ($this->geolocation !== null) {
            array_push($dataList, $this->geolocation);
        }
        return $dataList;
    }

    public function collectVisitorDataToAdd(): array
    {
        $dataList = [];
        array_push($dataList, ...array_values($this->customDataDict));
        array_push($dataList, ...array_values($this->pageViewVisits));
        array_push($dataList, ...$this->conversions);
        array_push($dataList, ...array_values($this->experiments));
        array_push($dataList, $this->previousVisitorVisits);
        if ($this->device !== null) {
            array_push($dataList, $this->device);
        }
        if ($this->browser !== null) {
            array_push($dataList, $this->browser);
        }
        if ($this->operatingSystem !== null) {
            array_push($dataList, $this->operatingSystem);
        }
        if ($this->geolocation !== null) {
            array_push($dataList, $this->geolocation);
        }
        return $dataList;
    }
}
