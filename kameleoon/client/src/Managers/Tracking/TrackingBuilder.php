<?php

declare(strict_types=1);

namespace Kameleoon\Managers\Tracking;

use Generator;
use Kameleoon\Configuration\DataFile;
use Kameleoon\Data\CustomData;
use Kameleoon\Data\Manager\AssignedVariation;
use Kameleoon\Data\Manager\LegalConsent;
use Kameleoon\Data\Manager\Visitor;
use Kameleoon\Data\Manager\VisitorManager;
use Kameleoon\Logging\KameleoonLogger;
use Kameleoon\Network\ActivityEvent;
use Kameleoon\Network\QueryParam;
use Kameleoon\Network\QueryParams;

class TrackingBuilder
{
    // input
    private array $visitorCodes;
    private DataFile $dataFile;
    private VisitorManager $visitorManager;
    // output
    private array $trackingLines;
    private array $unsentVisitorData;
    // variables
    private bool $built;

    public function __construct(array $visitorCodes, DataFile $dataFile, VisitorManager $visitorManager)
    {
        $this->visitorCodes = $visitorCodes;
        $this->dataFile = $dataFile;
        $this->visitorManager = $visitorManager;
        $this->built = false;
        $this->trackingLines = [];
        $this->unsentVisitorData = [];
    }

    public function &getTrackingLines(): array
    {
        return $this->trackingLines;
    }
    public function &getUnsentVisitorData(): array
    {
        return $this->unsentVisitorData;
    }

    private static function logVisitorTracking(string $visitorCode, bool $isConsentGiven, array $data): void
    {
        if ($data != null) {
            KameleoonLogger::debug(
                "Sending tracking request for unsent data %s of visitor '%s' with given (or not required) consent %s",
                $data,
                $visitorCode,
                $isConsentGiven,
            );
        } else {
            KameleoonLogger::debug(
                "No data to send for visitor '%s' with given (or not required) consent %s",
                $visitorCode,
                $isConsentGiven,
            );
        }
    }

    public function build(): void
    {
        if ($this->built) {
            return;
        }
        foreach ($this->visitorCodes as $visitorCode) {
            $visitor = $this->visitorManager->getVisitor($visitorCode);
            $isConsentGiven = $this->isConsentGiven($visitor);
            $data = $this->collectTrackingData($visitorCode, $visitor, $isConsentGiven);
            array_push($this->unsentVisitorData, ...$data);
            self::logVisitorTracking($visitorCode, $isConsentGiven, $data);
        }
        $this->built = true;
    }

    private function isConsentGiven(?Visitor $visitor): bool
    {
        $isConsentGiven = !$this->dataFile->getSettings()->isConsentRequired();
        return $isConsentGiven || (($visitor != null) && ($visitor->getLegalConsent() == LegalConsent::GIVEN));
    }

    private function collectTrackingData(string $visitorCode, ?Visitor $visitor, bool $isConsentGiven): array
    {
        $useMappingValue = $this->createSelfVisitorLinkIfRequired($visitorCode, $visitor);
        KameleoonLogger::info(function () use ($visitorCode, $useMappingValue) {
            $idType = $useMappingValue ? "mapping value" : "visitor code";
            return "'$visitorCode' was used as a $idType for visitor data tracking.";
        });
        $unsentData = self::selectUnsentVisitorData($visitor, $isConsentGiven);
        $this->collectTrackingLines($visitorCode, $visitor, $unsentData, $useMappingValue);
        return $unsentData;
    }

    private function createSelfVisitorLinkIfRequired(string $visitorCode, ?Visitor &$visitor): bool
    {
        $isMapped = ($visitor != null) && ($visitor->getMappingIdentifier() != null);
        $isUniqueIdentifier = ($visitor != null) && $visitor->isUniqueIdentifier();
        // need to find if anonymous visitor is behind unique (anonym doesn't exist if MappingIdentifier == null)
        if ($isUniqueIdentifier && !$isMapped) {
            // We haven't anonymous behind, in this case we should create "fake" anonymous with id == visitorCode
            // and link it with with mapping value == visitorCode (like we do as we have real anonymous visitor)
            $mi = new CustomData($this->dataFile->getCustomDataInfo()->getMappingIdentifierIndex(), $visitorCode);
            $visitor = $this->visitorManager->addData($visitorCode, true, $mi);
        }
        return $isUniqueIdentifier && ($visitorCode !== $visitor->getMappingIdentifier());
    }

    private static function selectUnsentVisitorData(?Visitor $visitor, bool $isConsentGiven): array
    {
        $data = [];
        if ($visitor != null) {
            if ($isConsentGiven) {
                array_push($data, ...$visitor->getUnsentData());
            } else {
                array_push(
                    $data,
                    ...array_filter($visitor->getAssignedVariations(), function ($assignedVariation) {
                        return !$assignedVariation->isSent() &&
                            $assignedVariation->getRuleType() === AssignedVariation::RULE_TYPE_TARGETED_DELIVERY;
                    })
                );
                array_push($data, ...$visitor->getUnsentConversions());
            }
        }
        if (empty($data) && $isConsentGiven) {
            $data[] = new ActivityEvent();
        }
        return $data;
    }

    private function collectTrackingLines(
        string $visitorCode,
        ?Visitor $visitor,
        array $data,
        bool $useMappingValue
    ): void {
        $userAgent = ($visitor != null) ? $visitor->getUserAgent() : null;
        $visitorCodeParam = (string) new QueryParam(
            $useMappingValue ? QueryParams::MAPPING_VALUE : QueryParams::VISITOR_CODE,
            $visitorCode,
        );
        foreach ($data as $sendable) {
            $line = $sendable->getQuery();
            if ($line != null) {
                $line = self::addLineParams($line, $visitorCodeParam, $userAgent);
                $this->trackingLines[] = $line;
                $userAgent = null; // Nullifying to ensure userAgent is used only once with the first line
            }
        }
    }
    private static function addLineParams(string $trackingLine, string $visitorCodeParam, ?string $userAgent): string
    {
        $trackingLine .= "&$visitorCodeParam";
        if ($userAgent != null) {
            $userAgentParam = (string) new QueryParam(QueryParams::USER_AGENT, $userAgent);
            $trackingLine .= "&$userAgentParam";
        }
        return $trackingLine;
    }
}
