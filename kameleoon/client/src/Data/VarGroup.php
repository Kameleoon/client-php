<?php

declare(strict_types=1);

namespace Kameleoon\Data;

/** @internal */
final class VarGroup
{
    private array $ids;

    public function __construct(array $ids)
    {
        sort($ids);
        $this->ids = $ids;
    }

    public function getIds(): array
    {
        return $this->ids;
    }

    public function __toString(): string
    {
        return "VarGroup{ids:" . json_encode($this->ids) . "}";
    }
}
