<?php

declare(strict_types=1);

namespace Kameleoon\Data\Manager;

use Traversable;
use Kameleoon\Logging\KameleoonLogger;
use Kameleoon\Configuration\CustomDataInfo;
use Kameleoon\Data\BaseData;
use Kameleoon\Data\Conversion;
use Kameleoon\Data\CustomData;
use Kameleoon\Data\MappingIdentifier;
use Kameleoon\Managers\Data\DataManager;

/** @internal */
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
        foreach ($this->visitors as $visitorCode => $visitor) {
            yield $visitorCode => $visitor;
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
            if ($d instanceof CustomData) {
                $d = $this->processCustomData($cdi, $visitorCode, $visitor, $d);
                if ($d !== null) {
                    $dataToAdd[] = $d;
                }
            } elseif ($d instanceof Conversion) {
                $dataToAdd[] = self::processConversion($d, $cdi);
            } else {
                $dataToAdd[] = $d;
            }
        }
        $visitor->addData(true, ...$dataToAdd);
        KameleoonLogger::debug("RETURN: VisitorManager->addData(visitorCode: '%s', data: %s) -> (visitor)",
            $visitorCode, $data);
        return $visitor;
    }

    private function processCustomData(
        ?CustomDataInfo $cdi, string $visitorCode, Visitor $visitor, CustomData $cd): ?CustomData
    {
        $mappedCd = self::tryMapCustomDataIndexByName($cd, $cdi);
        if ($mappedCd === null) {
            KameleoonLogger::error("%s is invalid and will be ignored", $cd);
            return null;
        }
        $cd = $mappedCd;
        if ($cdi === null) {
            return $cd;
        }
        // We shouldn't send custom data with local only type
        if ($cdi->isLocalOnly($cd->getIndex())) {
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

    private static function processConversion(Conversion $conv, ?CustomDataInfo $cdi): Conversion
    {
        if ($conv->getMetadata() !== null) {
            foreach ($conv->getMetadata() as $i => $cd) {
                $cd = self::tryMapCustomDataIndexByName($cd, $cdi);
                if ($cd === null) {
                    KameleoonLogger::warning("Conversion metadata %s is invalid", $conv->getMetadata()[$i]);
                }
                $conv->getMetadata()[$i] = $cd;
            }
        }
        return $conv;
    }

    private static function tryMapCustomDataIndexByName(CustomData $cd, ?CustomDataInfo $cdi): ?CustomData
    {
        if ($cd->getIndex() !== CustomData::UNDEFINED_INDEX) {
            return $cd;
        }
        if (($cd->getName() === null) || ($cdi === null)) {
            return null;
        }
        $cdIndex = $cdi->getCustomDataIndexByName($cd->getName());
        return ($cdIndex !== null) ? $cd->namedToIndexed($cdIndex) : null;
    }

    private static function isMappingIdentifier(CustomDataInfo $cdi, CustomData $cd): bool
    {
        return $cdi->isMappingIdentifier($cd->getIndex())
            && !empty($cd->getValues()) && (($cd->getValues()[0] ?? null) != null);
    }
}
