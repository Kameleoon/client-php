<?php

declare(strict_types=1);

namespace Kameleoon\Data;

final class ScoredVarId {
    public int $variationId;
    public float $score;

    public function __construct(int $variationId, float $score)
    {
        $this->variationId = $variationId;
        $this->score = $score;
    }
}
