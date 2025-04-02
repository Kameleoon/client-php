<?php

namespace Kameleoon\Data;

class Personalization implements BaseData
{
    private int $id;
    private int $variationId;

    public function __construct(int $id, int $variationId)
    {
        $this->id = $id;
        $this->variationId = $variationId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getVariationId(): int
    {
        return $this->variationId;
    }

    public function __toString(): string
    {
        return "Personalization{id:" . $this->id . ",variationId:" . $this->variationId . "}";
    }
}
