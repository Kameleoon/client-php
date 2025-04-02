<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Logging\KameleoonLogger;

class ExclusiveExperimentCondition extends TargetingCondition
{
    private const CAMPAIGN_TYPE_EXPERIMENT = "EXPERIMENT";
    private const CAMPAIGN_TYPE_PERSONALIZATION = "PERSONALIZATION";
    private const CAMPAIGN_TYPE_ANY = "ANY";

    const TYPE = "EXCLUSIVE_EXPERIMENT";

    private string $campaignType;

    public function __construct($conditionData)
    {
        parent::__construct($conditionData);
        $this->campaignType = $conditionData->campaignType ?? null;
    }

    public function check($data): bool
    {
        if (!is_array($data)) {
            return false;
        }
        $currentExperimentId = $data[0] ?? -1;
        $variations = $data[1] ?? [];
        $personalizations = $data[2] ?? [];
        switch ($this->campaignType) {
            case self::CAMPAIGN_TYPE_EXPERIMENT:
                return self::checkExperiment($currentExperimentId, $variations);
            case self::CAMPAIGN_TYPE_PERSONALIZATION:
                return self::checkPersonalization($personalizations);
            case self::CAMPAIGN_TYPE_ANY:
                return self::checkPersonalization($personalizations)
                    && self::checkExperiment($currentExperimentId, $variations);
        }
        KameleoonLogger::error(
            "Unexpected campaign type for 'ExclusiveExperimentCondition' condition: '%s'", $this->campaignType
        );
        return false;
    }

    private static function checkExperiment(int $currentExperimentId, array $variations): bool
    {
        return (count($variations) == 0) ||
            ((count($variations) == 1) && array_key_exists($currentExperimentId, $variations));
    }

    private static function checkPersonalization(array $personalizations): bool
    {
        return count($personalizations) == 0;
    }
}
