<?php

declare(strict_types=1);

namespace Kameleoon\Managers\RemoteData;

use Kameleoon\Data\Manager\VisitorManager;
use Kameleoon\Helpers\VisitorCodeManager;
use Kameleoon\Logging\KameleoonLogger;
use Kameleoon\Managers\Data\DataManager;
use Kameleoon\Network\NetworkManager;
use Kameleoon\Types\RemoteVisitorDataFilter;

class RemoteDataManagerImpl implements RemoteDataManager
{
    private NetworkManager $networkManager;
    private VisitorManager $visitorManager;
    private DataManager $dataManager;

    public function __construct(
        DataManager $dataManager, NetworkManager $networkManager, VisitorManager $visitorManager)
    {
        KameleoonLogger::debug("CALL: new RemoteDataManager(dataManager, networkManager, visitorManager)");
        $this->dataManager = $dataManager;
        $this->networkManager = $networkManager;
        $this->visitorManager = $visitorManager;
        KameleoonLogger::debug("RETURN: new RemoteDataManager(dataManager, networkManager, visitorManager)");
    }

    public function getData(string $key, ?int $timeout): ?object
    {
        KameleoonLogger::debug("CALL: RemoteDataManager->getData(key: '%s', timeout: %s)", $key, $timeout);
        $data = $this->networkManager->getRemoteData($key, $timeout);
        if ($data === null) {
            KameleoonLogger::error("Get remote data failed");
        }
        KameleoonLogger::debug(
            "RETURN: RemoteDataManager->getData(key: '%s', timeout: %s) -> (data: %s)", $key, $timeout, $data,
        );
        return $data;
    }

    public function getVisitorData(string $visitorCode, ?int $timeout,
        ?RemoteVisitorDataFilter $filter, bool $addData): array
    {
        KameleoonLogger::debug(
            "CALL: RemoteDataManager->getVisitorData(visitorCode: '%s', timeout: %s, addData: %s, filter: %s)",
            $visitorCode, $timeout, $addData, $filter,
        );
        // TODO: Uncomment with the next major update: https://project.kameleoon.net/issues/28072
        //VisitorCodeManager::validateVisitorCode($visitorCode);
        $visitor = $this->visitorManager->getVisitor($visitorCode);
        $isUniqueIdentifier = ($visitor != null) && $visitor->isUniqueIdentifier();
        if ($filter === null) {
            $filter = new RemoteVisitorDataFilter();
        }
        $json = $this->networkManager->getRemoteVisitorData($visitorCode, $filter, $isUniqueIdentifier, $timeout);
        if ($json === null) {
            KameleoonLogger::error("Get remote visitor data failed");
            return [];
        }
        $data = new RemoteVisitorData($json);
        $dataFile = $this->dataManager->getDataFile();
        $cdi = ($dataFile != null) ? $dataFile->getCustomDataInfo() : null;
        $data->markVisitorDataAsSent($cdi);
        if ($addData) {
            $visitor = $this->visitorManager->getOrCreateVisitor($visitorCode);
            $visitor->addData(false, ...$data->collectVisitorDataToAdd());
        }
        $dataToReturn = $data->collectVisitorDataToReturn();
        KameleoonLogger::debug(
            "CALL: RemoteDataManager->getVisitorData(visitorCode: '%s', timeout: %s, addData: %s, filter: %s)" .
            " -> (data: %s)", $visitorCode, $timeout, $addData, $filter, $dataToReturn,
        );
        return $dataToReturn;
    }
}
