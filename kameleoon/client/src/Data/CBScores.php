<?php

declare(strict_types=1);

namespace Kameleoon\Data;

use Kameleoon\Helpers\StringHelper;

final class CBScores implements BaseData
{
    // keys = experiment IDs / values = list of variation IDs ordered descending
    // by score (there may be several variation ids with same score)
    private array $values;

    public function __construct(array $cbsMap)
    {
        $values = [];
        foreach ($cbsMap as $cbsKey => $cbsValue) {
            $values[$cbsKey] = self::extractVarIds($cbsValue);
        }
        $this->values = $values;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    private static function extractVarIds(array &$scores): array
    {
        $grouped = [];
        foreach ($scores as $score) {
            $key = strval($score->score); // float keys are not very precise, so using strings instead
            $grouped[$key][] = $score->variationId;
        }
        krsort($grouped);
        $varIds = [];
        foreach ($grouped as $ids) {
            $varIds[] = new VarGroup($ids);
        }
        return $varIds;
    }

    public function __toString(): string
    {
        return "CBScores{values:" . StringHelper::objectToString($this->values) . "}";
    }
}
