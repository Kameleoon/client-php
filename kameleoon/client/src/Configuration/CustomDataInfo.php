<?php

declare(strict_types=1);

namespace Kameleoon\Configuration;

use Kameleoon\Logging\KameleoonLogger;

class CustomDataInfo
{
    private const SCOPE_VISITOR = "VISITOR";
    private const UNDEFINED_INDEX = -1;

    private array $localOnly;
    private array $visitorScope;
    private array $customDataIndexById;
    private int $mappingIdentifierIndex;

    public function __construct($json)
    {
        $this->localOnly = [];
        $this->visitorScope = [];
        $this->customDataIndexById = [];
        $this->mappingIdentifierIndex = self::UNDEFINED_INDEX;
        if (!is_array($json)) {
            return;
        }
        foreach ($json as $cd) {
            if ($cd == null) {
                continue;
            }
            if ($cd->localOnly ?? false) {
                array_push($this->localOnly, $cd->index ?? self::UNDEFINED_INDEX);
            }
            if (($cd->scope ?? null) == self::SCOPE_VISITOR) {
                array_push($this->visitorScope, $cd->index ?? self::UNDEFINED_INDEX);
            }
            $cdId = $cd->id ?? null;
            $cdIndex = $cd->index ?? null;
            if (($cdId !== null) && ($cdIndex !== null)) {
                $this->customDataIndexById[$cdId] = $cdIndex;
            }
            if ($cd->isMappingIdentifier ?? false) {
                if ($this->mappingIdentifierIndex !== self::UNDEFINED_INDEX) {
                    KameleoonLogger::warning("More than one mapping identifier is set. " .
                        "Undefined behavior may occur on cross-device reconciliation.");
                }
                $this->mappingIdentifierIndex = $cd->index ?? self::UNDEFINED_INDEX;
            }
        }
    }

    public function getMappingIdentifierIndex(): int
    {
        return $this->mappingIdentifierIndex;
    }

    public function isLocalOnly(int $index): bool
    {
        return in_array($index, $this->localOnly);
    }

    public function isVisitorScope(int $index): bool
    {
        return in_array($index, $this->visitorScope);
    }

    public function isMappingIdentifier(int $index): bool
    {
        return $index === $this->mappingIdentifierIndex;
    }

    public function getCustomDataIndexById(int $customDataId): ?int
    {
        return $this->customDataIndexById[$customDataId] ?? null;
    }
}
