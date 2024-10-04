<?php

declare(strict_types=1);

namespace Kameleoon\Data\Manager;

use Traversable;
use Kameleoon\Logging\KameleoonLogger;
use Kameleoon\Configuration\CustomDataInfo;
use Kameleoon\Data\BaseData;
use Kameleoon\Data\CustomData;
use Kameleoon\Data\MappingIdentifier;
use Kameleoon\Managers\Data\DataManager;

class VisitorManagerImpl implements VisitorManager
{
    private array $visitors;
    private DataManager $dataManager;

    public function __construct(DataManager $dataManager)
    {
        $this->visitors = [];
        $this->dataManager = $dataManager;
    }

    public function getOrCreateVisitor($visitorCode): Visitor
    {
        KameleoonLogger::debug("CALL: VisitorManager->getOrCreateVisitor(visitorCode: '%s')", $visitorCode);
        $visitor = $this->visitors[$visitorCode] ?? null;
        if (is_null($visitor)) {
            $visitor = new VisitorImpl();
            $this->visitors[$visitorCode] = $visitor;
        }
        KameleoonLogger::debug("RETURN: VisitorManager->getOrCreateVisitor(visitorCode: '%s') -> (visitor)",
            $visitorCode);
        return $visitor;
    }

    public function getVisitor($visitorCode): ?Visitor
    {
        KameleoonLogger::debug("CALL/RETURN: VisitorManager->getOrCreateVisitor(visitorCode: '%s')", $visitorCode);
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
        KameleoonLogger::debug("CALL: VisitorManager->addData(visitorCode: '%s', data: %s)", $visitorCode, $data);
        $visitor = $this->getOrCreateVisitor($visitorCode);
        $dataFile = $this->dataManager->getDataFile();
        $cdi = ($dataFile != null) ? $dataFile->getCustomDataInfo() : null;
        $dataToAdd = [];
        foreach ($data as $d) {
            if (($d instanceof CustomData) && ($cdi != null)) {
                $dataToAdd[] = $this->handleCustomData($cdi, $visitorCode, $visitor, $d);
            } else {
                $dataToAdd[] = $d;
            }
        }
        $visitor->addData(true, ...$dataToAdd);
        KameleoonLogger::debug("RETURN: VisitorManager->addData(visitorCode: '%s', data: %s) -> (visitor)",
            $visitorCode, $data);
        return $visitor;
    }

    private function handleCustomData(
        CustomDataInfo $cdi, string $visitorCode, Visitor $visitor, CustomData $cd): CustomData
    {
        // We shouldn't send custom data with local only type
        if ($cdi->isLocalOnly($cd->getId())) {
            $cd->markAsSent();
        }
        // If mappingIdentifier is passed, we should link anonymous visitor with real unique userId.
        // After authorization, customer must be able to continue work with userId, but hash for variation
        // should be calculated based on anonymous visitor code, that's why set MappingIdentifier to visitor.
        if (self::isMappingIdentifier($cdi, $cd)) {
            $userId = $cd->getValues()[0] ?? null;
            $visitor->setMappingIdentifier($visitorCode);
            if ($visitorCode != $userId) {
                $this->visitors[$userId] = $visitor->clone();
                KameleoonLogger::info("Linked anonymous visitor '%s' with user '%s'", $visitorCode, $userId);
            }
            return new MappingIdentifier($cd);
        }
        return $cd;
    }

    private static function isMappingIdentifier(CustomDataInfo $cdi, CustomData $cd): bool
    {
        return $cdi->isMappingIdentifier($cd->getId())
            && !empty($cd->getValues()) && (($cd->getValues()[0] ?? null) != null);
    }
}
