<?php

declare(strict_types=1);

namespace Kameleoon\Storage;

class VisitorVariation
{
    private int $variationId;
    public function getVariationId(): int
    {
        return $this->variationId;
    }

    private int $assignmentDate;
    public function getAssignmentDate(): int
    {
        return $this->assignmentDate;
    }

    public function __construct(int $variationId)
    {
        $this->variationId = $variationId;
        $this->assignmentDate = time();
    }

    public function isValid(?int $respoolTime): bool
    {
        return ($respoolTime == null) || ($this->assignmentDate >= $respoolTime);
    }
}
