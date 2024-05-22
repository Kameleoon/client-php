<?php

declare(strict_types=1);

namespace Kameleoon\Managers\RemoteData;

use Kameleoon\Data\Manager\VisitorManager;
use Kameleoon\Helpers\VisitorCodeManager;
use Kameleoon\Network\NetworkManager;
use Kameleoon\Types\RemoteVisitorDataFilter;

class RemoteDataManagerImpl implements RemoteDataManager
{
    private NetworkManager $networkManager;
    private VisitorManager $visitorManager;

    public function __construct(NetworkManager $networkManager, VisitorManager $visitorManager)
    {
        $this->networkManager = $networkManager;
        $this->visitorManager = $visitorManager;
    }

    public function getData(string $key, ?int $timeout): ?object
    {
        $data = $this->networkManager->getRemoteData($key, $timeout);
        if ($data === null) {
            error_log("Get remote data failed");
        }
        return $data;
    }

    public function getVisitorData(string $visitorCode, ?int $timeout,
        ?RemoteVisitorDataFilter $filter, bool $addData, bool $isUniqueIdentifier): array
    {
        // TODO: Uncomment with the next major update: https://project.kameleoon.net/issues/28072
        //VisitorCodeManager::validateVisitorCode($visitorCode);
        if ($filter === null) {
            $filter = new RemoteVisitorDataFilter();
        }
        $json = $this->networkManager->getRemoteVisitorData($visitorCode, $filter, $isUniqueIdentifier, $timeout);
        if ($json === null) {
            error_log("Get remote visitor data failed");
            return [];
        }
        $data = new RemoteVisitorData($json);
        $data->markVisitorDataAsSent($this->visitorManager->getCustomDataInfo());
        if ($addData) {
            $visitor = $this->visitorManager->getOrCreateVisitor($visitorCode);
            $visitor->addData(false, ...$data->collectVisitorDataToAdd());
        }
        return $data->collectVisitorDataToReturn();
    }
}
