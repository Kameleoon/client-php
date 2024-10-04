<?php

declare(strict_types=1);

namespace Kameleoon\Managers\Hybrid;

use Kameleoon\Helpers\StringHelper;
use Kameleoon\Logging\KameleoonLogger;
use Kameleoon\Managers\Data\DataManager;

class HybridManagerImpl implements HybridManager
{
    public const TC_INIT = 'window.kameleoonQueue=window.kameleoonQueue||[];';
    public const TC_ASSIGN_VARIATION_FORMAT = "window.kameleoonQueue.push(['Experiments.assignVariation',%d,%d,true]);";
    public const TC_TRIGGER_FORMAT = "window.kameleoonQueue.push(['Experiments.trigger',%d,%s]);";

    private DataManager $dataManager;

    public function __construct(DataManager $dataManager)
    {
        $this->dataManager = $dataManager;
    }

    public function getEngineTrackingCode(?array $assignedVariations): string
    {
        KameleoonLogger::debug(
            "CALL: HybridManagerImpl->getEngineTrackingCode(assignedVariations: %s)", $assignedVariations);
        $res = HybridManagerImpl::TC_INIT;
        if (is_null($assignedVariations)) {
            return $res;
        }
        foreach ($assignedVariations as $experimentId => $variation) {
            $trackingOnly = !$this->dataManager->getDataFile()->hasExperimentJsCssVariable($experimentId);
            $res .= sprintf(
                HybridManagerImpl::TC_ASSIGN_VARIATION_FORMAT,
                $experimentId,
                $variation->getVariationId()
            ) . sprintf(HybridManagerImpl::TC_TRIGGER_FORMAT, $experimentId, StringHelper::strval($trackingOnly));
        }
        KameleoonLogger::debug(
            "CALL: HybridManagerImpl->getEngineTrackingCode(assignedVariations: %s) -> (trackingCode: '%s')",
            $assignedVariations, $res);
        return $res;
    }
}
