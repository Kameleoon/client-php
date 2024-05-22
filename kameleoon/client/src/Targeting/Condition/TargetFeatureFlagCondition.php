<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Configuration\DataFile;

class TargetFeatureFlagCondition extends TargetingCondition
{
    const TYPE = "TARGET_FEATURE_FLAG";

    private int $featureFlagId;
    private ?string $conditionVariationKey;
    private int $conditionRuleId;

    public function __construct($conditionData)
    {
        parent::__construct($conditionData);
        $this->featureFlagId = intval($conditionData->featureFlagId ?? "-1");
        $this->conditionVariationKey = $conditionData->variationKey ?? null;
        $this->conditionRuleId = intval($conditionData->ruleId ?? null);
    }

    public function check($data): bool
    {
        if (!is_array($data)) {
            return false;
        }
        $dataFile = $data[0] ?? null;
        $variations = $data[1] ?? [];
        if (($dataFile == null) || (count($variations) == 0)) {
            return false;
        }
        foreach ($this->getRules($dataFile) as $rule) {
            if (($rule == null) || ($rule->experimentId == null)) {
                continue;
            }
            $assignedVariation = $variations[$rule->experimentId] ?? null;
            if ($assignedVariation == null) {
                continue;
            }
            $variationId = $assignedVariation->getVariationId();
            if ($this->conditionVariationKey == null) {
                return true;
            }
            $variation = $dataFile->getVariation($variationId);
            if (($variation != null) && ($variation->variationKey === $this->conditionVariationKey)) {
                return true;
            }
        }
        return false;
    }

    private function getRules(DataFile $dataFile): array
    {
        $ff = $dataFile->getFeatureFlagById($this->featureFlagId);
        if ($ff !== null) {
            if ($this->conditionRuleId > 0) {
                foreach ($ff->rules as $rule) {
                    if ($rule->id === $this->conditionRuleId) {
                        return [$rule];
                    }
                }
            } else {
                return $ff->rules;
            }
        }
        return [];
    }
}
