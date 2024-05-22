<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Data\Geolocation;

class GeolocationCondition extends TargetingCondition
{
    const TYPE = "GEOLOCATION";

    private ?string $country;
    private ?string $region;
    private ?string $city;

    public function __construct($conditionData)
    {
        parent::__construct($conditionData);
        $this->country = $conditionData->country ?? null;
        $this->region = $conditionData->region ?? null;
        $this->city = $conditionData->city ?? null;
    }

    public function check($data): bool
    {
        if (!($data instanceof Geolocation)) {
            return false;
        }
        return (($this->country != null) && (strcasecmp($this->country, $data->getCountry() ?? "") === 0)) &&
            (($this->region == null) || (strcasecmp($this->region, $data->getRegion() ?? "") === 0)) &&
            (($this->city == null) || (strcasecmp($this->city, $data->getCity() ?? "") === 0));
    }
}
