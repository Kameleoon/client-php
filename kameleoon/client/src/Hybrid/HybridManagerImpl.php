<?php

declare(strict_types=1);

namespace Kameleoon\Hybrid;

class HybridManagerImpl implements HybridManager
{
    public const TC_INIT = 'window.kameleoonQueue=window.kameleoonQueue||[];';
    public const TC_ASSIGN_VARIATION_FORMAT = "window.kameleoonQueue.push(['Experiments.assignVariation',%d,%d]);";
    public const TC_TRIGGER_FORMAT = "window.kameleoonQueue.push(['Experiments.trigger',%d,true]);";

    public function getEngineTrackingCode(?array $assignedVariations): string
    {
        $res = HybridManagerImpl::TC_INIT;
        if (is_null($assignedVariations)) {
            return $res;
        }
        foreach ($assignedVariations as $experimentId => $variation) {
            $res .= sprintf(
                HybridManagerImpl::TC_ASSIGN_VARIATION_FORMAT,
                $experimentId,
                $variation->getVariationId()
            ) . sprintf(HybridManagerImpl::TC_TRIGGER_FORMAT, $experimentId);
        }
        return $res;
    }
}
