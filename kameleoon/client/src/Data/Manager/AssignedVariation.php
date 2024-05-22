<?php

declare(strict_types=1);

namespace Kameleoon\Data\Manager;

use Kameleoon\Data\BaseData;

interface AssignedVariation extends BaseData
{
    const RULE_TYPE_UNKNOWN = -1;
    const RULE_TYPE_EXPERIMENTATION = 0;
    const RULE_TYPE_TARGETED_DELIVERY = 1;

    public function getVariationId(): int;
    public function getExperimentId(): int;
    public function getRuleType(): int;
    public function isValid(?int $respoolTime): bool;
    public function getAssignmentDate(): ?int;
}
