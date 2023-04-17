<?php

declare(strict_types=1);

namespace Kameleoon\Storage;

interface VariationStorage
{
    public function getSavedVariation(string $visitorCode, int $experimentId): ?int;
    public function isVariationIdValid(string $visitorCode, int $experimentId, int $respoolTime): ?int;
    public function saveVariation(string $visitorCode, int $experimentId, int $variationId): void;
    public function getSavedVariations(string $visitorCode): ?array;
}
