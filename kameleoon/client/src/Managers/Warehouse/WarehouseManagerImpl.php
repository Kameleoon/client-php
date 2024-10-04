<?php

namespace Kameleoon\Managers\Warehouse;

use Kameleoon\Data\CustomData;
use Kameleoon\Data\Manager\VisitorManager;
use Kameleoon\Helpers\VisitorCodeManager;
use Kameleoon\Logging\KameleoonLogger;
use Kameleoon\Network\NetworkManager;

class WarehouseManagerImpl implements WarehouseManager
{
    public const WAREHOUSE_AUDIENCES_FIELD_NAME = "warehouseAudiences";

    private NetworkManager $networkManager;
    private VisitorManager $visitorManager;

    public function __construct($networkManager, $visitorManager)
    {
        KameleoonLogger::debug("CALL: new WarehouseManager(networkManager, visitorManager)");
        $this->networkManager = $networkManager;
        $this->visitorManager = $visitorManager;
        KameleoonLogger::debug("RETURN: new WarehouseManager(networkManager, visitorManager)");
    }

    public function getVisitorWarehouseAudience(
        string $visitorCode,
        int $customDataIndex,
        ?string $warehouseKey = null,
        ?int $timeout = null
    ): ?CustomData {
        KameleoonLogger::debug(
            "CALL: WarehouseManager->getVisitorWarehouseAudience(visitorCode: '%s', customDataIndex: %s, warehouseKey: '%s', timeout: %s)",
            $visitorCode, $customDataIndex, $warehouseKey, $timeout);
        VisitorCodeManager::validateVisitorCode($visitorCode);
        $remoteDataKey = empty($warehouseKey) ? $visitorCode : $warehouseKey;

        $remoteData = $this->networkManager->getRemoteData($remoteDataKey, $timeout);
        if ($remoteData == null) {
            KameleoonLogger::error("Failed to fetch visitor warehouse audience");
            return null;
        }
        $warehouseAudiences = $remoteData->{self::WAREHOUSE_AUDIENCES_FIELD_NAME} ?? [];
        $dataValues = array_keys((array)$warehouseAudiences);

        $warehouseAudiencesData = new CustomData($customDataIndex, ...$dataValues);
        $visitor = $this->visitorManager->getOrCreateVisitor($visitorCode);
        $visitor->addData(true, $warehouseAudiencesData);

        KameleoonLogger::debug(
            "RETURN: WarehouseManager->getVisitorWarehouseAudience(visitorCode: '%s', customDataIndex: %s," .
        " warehouseKey: '%s', timeout: %s) -> (warehouseAudiencesData: %s)",
            $visitorCode, $customDataIndex, $warehouseKey, $timeout, $warehouseAudiencesData);
        return $warehouseAudiencesData;
    }
}
