<?php

namespace Kameleoon\Managers\Warehouse;

use Kameleoon\Data\CustomData;
use Kameleoon\Data\Manager\VisitorManager;
use Kameleoon\Helpers\VisitorCodeManager;
use Kameleoon\Network\NetworkManager;

class WarehouseManagerImpl implements WarehouseManager
{
    public const WAREHOUSE_AUDIENCES_FIELD_NAME = "warehouseAudiences";

    private NetworkManager $networkManager;
    private VisitorManager $visitorManager;

    public function __construct($networkManager, $visitorManager)
    {
        $this->networkManager = $networkManager;
        $this->visitorManager = $visitorManager;
    }

    public function getVisitorWarehouseAudience(
        string $visitorCode,
        int $customDataIndex,
        ?string $warehouseKey = null,
        ?int $timeout = null
    ): ?CustomData {
        VisitorCodeManager::validateVisitorCode($visitorCode);
        $remoteDataKey = empty($warehouseKey) ? $visitorCode : $warehouseKey;

        $remoteData = $this->networkManager->getRemoteData($remoteDataKey, $timeout);
        if ($remoteData == null) {
            error_log("Kameleoon SDK: Failed to fetch visitor warehouse audience");
            return null;
        }
        $warehouseAudiences = $remoteData->{self::WAREHOUSE_AUDIENCES_FIELD_NAME} ?? [];
        $dataValues = array_keys((array)$warehouseAudiences);

        $warehouseAudiencesData = new CustomData($customDataIndex, ...$dataValues);
        $visitor = $this->visitorManager->getOrCreateVisitor($visitorCode);
        $visitor->addData(true, $warehouseAudiencesData);

        return $warehouseAudiencesData;
    }
}
