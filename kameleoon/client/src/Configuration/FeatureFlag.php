<?php

namespace Kameleoon\Configuration;

class FeatureFlag extends TargetingObject
{
    private const STATUS_ACTIVE = "ACTIVE";
    private const FEATURE_STATUS_DEACTIVATED = "DEACTIVATED";
    public $variationConfigurations;
    public $targetingSegment;
    public $identificationKey;
    public $isSiteCodeEnabled;
    private $featureStatus;
    private $status;
    private $schedules;

    public function __construct($ff)
    {
        parent::__construct($ff);
        $this->variationConfigurations = array();
        $this->identificationKey = isset($ff->identificationKey) ? $ff->identificationKey : null;

        $this->targetingSegment = null;

        $deviations = array();
        array_push($deviations,
            ["variationId" => 0, "value" => floatval(1 - $ff->expositionRate)],
            ["variationId" => $ff->variations[0]->id, "value" => floatval($ff->expositionRate)],
        );

        foreach ($deviations as $deviation) {
            $variationId = $deviation["variationId"] == "origin" ? 0 : intval($deviation["variationId"]);
            $deviationValue = floatval($deviation["value"]);
            $respoolTime = null;
            if (isset($ff->respoolTime)) {
                foreach ($ff->respoolTime as $rt) {
                    if ($rt->variationId == $variationId || ($variationId == 0 && $rt->variationId == "origin")) {
                        $respoolTime = floatval($rt->value);
                    }
                }
            }
            $customJson = '{}';
            foreach ($ff->variations as $variation) {
                if ($variation->id == $variationId || ($variationId == 0 && $variation->id == "origin")) {
                    $customJson = $variation->customJson;
                }
            }
            $this->variationConfigurations[$variationId] = new VariationConfiguration($deviationValue, $respoolTime, $customJson);
        }

        // Temporary fix for ff
        foreach ($ff->variations as $variation) {
            if (!isset($this->variationConfigurations[$variation->id]))
            {
                $deviation = 1 - $this->variationConfigurations["0"]->deviation;
                $respoolTime = null;
                if (isset($ff->respoolTime)) {
                    if (isset($ff->respoolTime[$variation->id])) {
                        $respoolTime = floatval($ff->respoolTime[$variation->id]);
                    }
                }
                $customJson = $variation->customJson;
                $this->variationConfigurations[$variation->id] = new VariationConfiguration($deviation, $respoolTime, $customJson);
            }
        }

        $this->status = $ff->status;
        $this->featureStatus = $ff->featureStatus;

        $this->schedules = array();
        if(isset($ff->schedules)) {
            foreach($ff->schedules as $schedule) {
                array_push($this->schedules, new Schedule($schedule));
            }
        }

        $this->isSiteCodeEnabled = $ff->siteEnabled;
    }

    public function isScheduleActive($currentTimestamp) {
        // if no schedules then need to fetch current status
        $currentStatus = $this->status == $this::STATUS_ACTIVE;
        if($this->featureStatus == $this::FEATURE_STATUS_DEACTIVATED || empty($this->schedules)) {
            return $currentStatus;
        }
        // need to find if date is in any period -> active or not -> not activate
        foreach($this->schedules as $schedule) {
            if (($schedule->dateStart == null || $schedule->dateStart < $currentTimestamp) &&
                ($schedule->dateEnd == null || $schedule->dateEnd > $currentTimestamp) ) {
                    return true;
            }
        }
        return false;
    }
}

?>
