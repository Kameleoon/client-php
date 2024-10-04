<?php

declare(strict_types=1);

namespace Kameleoon\Types;

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
        bool $kcs = false)
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
    }

    public function __toString(): string
    {
        return "RemoteVisitorDataFilter{" .
            "previousVisitAmount:" . $this->previousVisitAmount .
            ",currentVisit:" . ($this->currentVisit ? 'true' : 'false') .
            ",customData:" . ($this->customData ? 'true' : 'false') .
            ",pageViews:" . ($this->pageViews ? 'true' : 'false') .
            ",geolocation:" . ($this->geolocation ? 'true' : 'false') .
            ",device:" . ($this->device ? 'true' : 'false') .
            ",browser:" . ($this->browser ? 'true' : 'false') .
            ",operatingSystem:" . ($this->operatingSystem ? 'true' : 'false') .
            ",conversion:" . ($this->conversion ? 'true' : 'false') .
            ",experiments:" . ($this->experiments ? 'true' : 'false') .
            '}';
    }
}
