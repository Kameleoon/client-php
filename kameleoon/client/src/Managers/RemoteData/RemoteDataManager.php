<?php

declare(strict_types=1);

namespace Kameleoon\Managers\RemoteData;

use Kameleoon\Types\RemoteVisitorDataFilter;

interface RemoteDataManager
{
    public function getData(string $key, ?int $timeout): ?object;

    public function getVisitorData(string $visitorCode, ?int $timeout,
        ?RemoteVisitorDataFilter $filter, bool $addData, bool $isUniqueIdentifier): array;
}
