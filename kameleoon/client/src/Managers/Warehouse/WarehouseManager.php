<?php

namespace Kameleoon\Managers\Warehouse;

use Kameleoon\Data\CustomData;

interface WarehouseManager
{
    public function getVisitorWarehouseAudience(
        string $visitorCode,
        int $customDataIndex,
        ?string $warehouseKey = null,
        ?int $timeout = null
    ): ?CustomData;
}
