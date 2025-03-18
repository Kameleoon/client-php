<?php

declare(strict_types=1);

namespace Kameleoon\Types;

use Kameleoon\Helpers\StringHelper;

class RemoteVisitorDataFilter
{
    public int $previousVisitAmount;
    public bool $currentVisit;
    public bool $customData;
    public bool $pageViews;
    public bool $geolocation;
    public bool $device;
    public bool $browser;
    public bool $operatingSystem;
    public bool $conversion;
    public bool $experiments;
    public bool $kcs;
    public bool $visitorCode;
    public bool $cbs;

    public function __construct(
        int $previousVisitAmount = 1,
        bool $currentVisit = true,
        bool $customData = true,
        bool $pageViews = false,
        bool $geolocation = false,
        bool $device = false,
        bool $browser = false,
        bool $operatingSystem = false,
        bool $conversion = false,
        bool $experiments = false,
        bool $kcs = false,
        bool $visitorCode = true,
        bool $cbs = false)
    {
        $this->previousVisitAmount = $previousVisitAmount;
        $this->currentVisit = $currentVisit;
        $this->customData = $customData;
        $this->pageViews = $pageViews;
        $this->geolocation = $geolocation;
        $this->device = $device;
        $this->browser = $browser;
        $this->operatingSystem = $operatingSystem;
        $this->conversion = $conversion;
        $this->experiments = $experiments;
        $this->kcs = $kcs;
        $this->visitorCode = $visitorCode;
        $this->cbs = $cbs;
    }

    public function __toString(): string
    {
        return "RemoteVisitorDataFilter{" .
            "previousVisitAmount:" . $this->previousVisitAmount .
            ",currentVisit:" . StringHelper::sbool($this->currentVisit) .
            ",customData:" . StringHelper::sbool($this->customData) .
            ",pageViews:" . StringHelper::sbool($this->pageViews) .
            ",geolocation:" . StringHelper::sbool($this->geolocation) .
            ",device:" . StringHelper::sbool($this->device) .
            ",browser:" . StringHelper::sbool($this->browser) .
            ",operatingSystem:" . StringHelper::sbool($this->operatingSystem) .
            ",conversion:" . StringHelper::sbool($this->conversion) .
            ",experiments:" . StringHelper::sbool($this->experiments) .
            ",kcs:" . StringHelper::sbool($this->kcs) .
            ",visitorCode:" . StringHelper::sbool($this->visitorCode) .
            ",cbs:" . StringHelper::sbool($this->cbs) .
            '}';
    }
}
