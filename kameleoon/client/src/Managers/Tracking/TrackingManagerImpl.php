<?php

declare(strict_types=1);

namespace Kameleoon\Managers\Tracking;

use Kameleoon\Data\Manager\VisitorManager;
use Kameleoon\Logging\KameleoonLogger;
use Kameleoon\Managers\Data\DataManager;
use Kameleoon\Network\NetworkManager;

class TrackingManagerImpl implements TrackingManager
{
    private const LINES_DELIMITER = "\n";

    private DataManager $dataManager;
    private NetworkManager $networkManager;
    private VisitorManager $visitorManager;
    private bool $debug;

    public function __construct(
        DataManager $dataManager, NetworkManager $networkManager, VisitorManager $visitorManager, bool $debug)
    {
        KameleoonLogger::debug("CALL: new TrackingManagerImpl(dataManager, networkManager, visitorManager)");
        $this->dataManager = $dataManager;
        $this->networkManager = $networkManager;
        $this->visitorManager = $visitorManager;
        $this->debug = $debug;
        KameleoonLogger::debug("RETURN: new TrackingManagerImpl(dataManager, networkManager, visitorManager)");
    }

    public function trackVisitor(string $visitorCode, bool $instant = false, ?int $timeout = null): void
    {
        KameleoonLogger::debug(
            "CALL: TrackingManagerImpl->trackVisitor(visitorCode: '%s', instant: %s, timeout: %s)",
            $visitorCode, $instant, $timeout,
        );
        $this->track([$visitorCode], $instant, $timeout);
        KameleoonLogger::debug(
            "RETURN: TrackingManagerImpl->trackVisitor(visitorCode: '%s', instant: %s, timeout: %s)",
            $visitorCode, $instant, $timeout,
        );
    }
    public function trackAll(bool $instant = false, ?int $timeout = null): void
    {
        KameleoonLogger::debug("CALL: TrackingManagerImpl->trackAll(instant: %s, timeout: %s)", $instant, $timeout);
        $visitorCodes = [];
        foreach ($this->visitorManager as $visitorCode => $visitor) {
            if (!empty($visitor->getUnsentData())) {
                $visitorCodes[] = $visitorCode;
            }
        }
        $this->track($visitorCodes, $instant, $timeout);
        KameleoonLogger::debug("RETURN: TrackingManagerImpl->trackAll(instant: %s, timeout: %s)", $instant, $timeout);
    }

    private function track(array $visitorCodes, bool $instant, ?int $timeout): void
    {
        $dataFile = $this->dataManager->getDataFile();
        if ($dataFile == null) {
            KameleoonLogger::error("Tracking requires data file but it is not loaded.");
            return;
        }
        $builder = new TrackingBuilder($visitorCodes, $dataFile, $this->visitorManager);
        $builder->build();
        $this->performTrackingRequest(
            $builder->getUnsentVisitorData(), $builder->getTrackingLines(), $instant, $timeout,
        );
    }

    private function performTrackingRequest(
        array &$visitorData, array &$trackingLines, bool $instant, ?int $timeout): void
    {
        if ($trackingLines == null) {
            return;
        }
        KameleoonLogger::debug(
            "CALL: TrackingManagerImpl->performTrackingRequest(visitorData: %s, trackingLines: %s, " .
            "instant: %s, timeout: %s)", $visitorData, $trackingLines, $instant, $timeout,
        );
        $trackingLines[] = ""; // Additional empty line to make $lines end with LF character
        $lines = implode(self::LINES_DELIMITER, $trackingLines);
        if ($instant) {
            $sent = $this->networkManager->sendTrackingDataInstantly($lines, $this->debug, $timeout);
        } else {
            $this->networkManager->sendTrackingData($lines, $this->debug);
            $sent = true;
        }
        if ($sent) {
            foreach ($visitorData as $sendable) {
                $sendable->markAsSent();
            }
        }
        KameleoonLogger::debug(
            "RETURN: TrackingManagerImpl->performTrackingRequest(visitorData: %s, trackingLines: %s, " .
            "instant: %s, timeout: %s)", $visitorData, $trackingLines, $instant, $timeout,
        );
    }
}
