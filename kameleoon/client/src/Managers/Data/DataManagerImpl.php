<?php

declare(strict_types=1);

namespace Kameleoon\Managers\Data;

use Kameleoon\Configuration\DataFile;
use Kameleoon\Logging\KameleoonLogger;

class DataManagerImpl implements DataManager
{
    private ?DataFile $dataFile;
    private bool $consentRequired;

    public function __construct(?DataFile $dataFile = null)
    {
        KameleoonLogger::debug("CALL: new DataManagerImpl(dataFile: %s)", $dataFile);
        if ($dataFile != null) {
            $this->setDataFile($dataFile);
        } else {
            $this->dataFile = null;
            $this->consentRequired = false;
        }
        KameleoonLogger::debug("RETURN: new DataManagerImpl(dataFile: %s)", $dataFile);
    }

    public function doesVisitorCodeManagementRequireConsent(): bool
    {
        return $this->consentRequired;
    }

    public function getDataFile(): ?DataFile
    {
        return $this->dataFile;
    }

    public function setDataFile(DataFile $df): void
    {
        KameleoonLogger::debug("CALL: DataManagerImpl->setDataFile(df: %s)", $df);
        $this->dataFile = $df;
        $this->consentRequired = $df->getSettings()->isConsentRequired() &&
            !$this->dataFile->hasAnyTargetedDeliveryRule();
        KameleoonLogger::debug("RETURN: DataManagerImpl->setDataFile(df: %s)", $df);
    }
}
