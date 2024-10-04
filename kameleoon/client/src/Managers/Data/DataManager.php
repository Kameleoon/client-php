<?php

declare(strict_types=1);

namespace Kameleoon\Managers\Data;

use Kameleoon\Configuration\DataFile;

interface DataManager
{
    public function doesVisitorCodeManagementRequireConsent(): bool;

    public function getDataFile(): ?DataFile;

    public function setDataFile(DataFile $df): void;
}
