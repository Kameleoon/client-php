<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Targeting\TargetingEngine;

class SegmentCondition extends TargetingCondition
{
    const TYPE = "SEGMENT";

    private int $segmentId;

    public function __construct($conditionData)
    {
        parent::__construct($conditionData);
        $this->segmentId = intval($conditionData->segmentId ?? null);
    }

    public function check($data): bool
    {
        if (!is_array($data)) {
            return false;
        }
        $dataFile = $data[0] ?? null;
        $getTargetedData = $data[1] ?? null;
        if (($dataFile == null) || ($getTargetedData == null)) {
            return false;
        }
        $rule = $dataFile->getRuleBySegmentId($this->segmentId);
        if ($rule === null) {
            return false;
        }
        $targetingSegment = $rule->getTargetingSegment();
        if ($targetingSegment === null) {
            return false;
        }
        $targetingTree = $targetingSegment->getTargetingTree();
        return ($targetingTree !== null) && TargetingEngine::checkTargetingTree($targetingTree, $getTargetedData);
    }
}
