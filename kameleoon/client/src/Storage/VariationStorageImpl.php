<?php

declare(strict_types=1);

namespace Kameleoon\Storage;

class VariationStorageImpl implements VariationStorage
{
    private $mapVariations = array();

    public function getSavedVariation(string $visitorCode, int $experimentId): ?int
    {
        return $this->isVariationIdValid($visitorCode, $experimentId, null);
    }

    public function isVariationIdValid(string $visitorCode, int $experimentId, ?int $respoolTime): ?int
    {
        $variation = ($this->mapVariations[$visitorCode] ?? [])[$experimentId] ?? null;
        if (is_null($variation) || !$variation->isValid($respoolTime)) {
            return null;
        }
        return $variation->getVariationId();
    }

    public function saveVariation($visitorCode, $experimentId, $variationId): void
    {
        $this->mapVariations[$visitorCode][$experimentId] = new VisitorVariation($variationId);
    }

    public function getSavedVariations($visitorCode): ?array
    {
        $visitorVariations = $this->mapVariations[$visitorCode] ?? null;
        if (is_null($visitorVariations)) {
            return null;
        }
        return array_combine(
            array_keys($visitorVariations),
            array_map(
                function ($variation) {
                    return $variation->getVariationId();
                },
                $visitorVariations
            )
        );
    }
}
