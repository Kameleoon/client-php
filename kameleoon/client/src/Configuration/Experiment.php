<?php

declare(strict_types=1);

namespace Kameleoon\Configuration;

use Kameleoon\Exception\FeatureVariationNotFound;

class Experiment
{
    public int $id;
    public array $variationsByExposition;

    public function __construct($obj)
    {
        $this->id = is_int($obj->experimentId) ? $obj->experimentId : 0;
        $this->variationsByExposition = array_map(
            fn ($var) => new VariationByExposition($var),
            $obj->variationByExposition
        );
    }

    public function getVariationByHash(float $hash): ?VariationByExposition
    {
        $total = 0.0;
        foreach ($this->variationsByExposition as $variationByExposition) {
            $total += $variationByExposition->exposition;
            if ($total >= $hash) {
                return $variationByExposition;
            }
        }
        return null;
    }

    public function getVariationIdByKey(string $key): ?int
    {
        foreach ($this->variationsByExposition as $varByExp) {
            if ($varByExp->variationKey == $key) {
                return $varByExp;
            }
        }
        return null;
    }

    public function getVariationByKey(string $variationKey): VariationByExposition
    {
        foreach ($this->variationsByExposition as $varByExp) {
            if ($varByExp->variationKey == $variationKey) {
                return $varByExp;
            }
        }
        throw new FeatureVariationNotFound("{$this} does not contain variation '{$variationKey}'");
    }

    public function __toString(): string
    {
        return "Experiment{id:$this->id}";
    }
}
