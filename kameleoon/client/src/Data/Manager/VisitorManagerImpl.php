<?php

declare(strict_types=1);

namespace Kameleoon\Data\Manager;

use Traversable;
use Kameleoon\Configuration\CustomDataInfo;
use Kameleoon\Data\BaseData;
use Kameleoon\Data\CustomData;
use Kameleoon\Data\Manager\VisitorImpl;

class VisitorManagerImpl implements VisitorManager
{
    private array $visitors;
    private ?CustomDataInfo $customDataInfo;

    public function __construct()
    {
        $this->visitors = [];
    }

    public function getCustomDataInfo(): ?CustomDataInfo
    {
        return $this->customDataInfo ?? null;
    }

    public function setCustomDataInfo(?CustomDataInfo $value): void
    {
        $this->customDataInfo = $value;
    }

    public function getOrCreateVisitor($visitorCode): Visitor
    {
        $visitor = $this->visitors[$visitorCode] ?? null;
        if (is_null($visitor)) {
            $visitor = new VisitorImpl();
            $this->visitors[$visitorCode] = $visitor;
        }
        return $visitor;
    }

    public function getVisitor($visitorCode): ?Visitor
    {
        return $this->visitors[$visitorCode] ?? null;
    }

    public function getIterator(): Traversable
    {
        foreach ($this->visitors as $value) {
            yield $value;
        }
    }

    public function addData(string $visitorCode, BaseData ...$data): Visitor
    {
        $visitor = $this->getOrCreateVisitor($visitorCode);
        if (isset($this->customDataInfo) && ($this->customDataInfo !== null)) {
            foreach ($data as $d) {
                if ($d instanceof CustomData) {
                    $this->handleCustomData($visitorCode, $visitor, $d);
                }
            }
        }
        $visitor->addData(true, ...$data);
        return $visitor;
    }

    private function handleCustomData(string $visitorCode, Visitor $visitor, CustomData $cd): void
    {
        // We shouldn't send custom data with local only type
        if ($this->customDataInfo->isLocalOnly($cd->getId())) {
            $cd->markAsSent();
        }
        // If mappingIdentifier is passed, we should link anonymous visitor with real unique userId.
        // After authorization, customer must be able to continue work with userId, but hash for variation
        // should be calculated based on anonymous visitor code, that's why set MappingIdentifier to visitor.
        if ($this->customDataInfo->isMappingIdentifier($cd->getId()) && !empty($cd->getValues())) {
            $targetVisitorCode = $cd->getValues()[0] ?? null;
            if ($targetVisitorCode != null) {
                $cd->setIsMappingIdentifier(true);
                $visitor->setMappingIdentifier($visitorCode);
                if ($visitorCode != $targetVisitorCode) {
                    $this->visitors[$targetVisitorCode] = $visitor;
                }
            }
        }
    }
}
