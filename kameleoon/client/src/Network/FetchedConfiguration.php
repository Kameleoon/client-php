<?php

declare(strict_types=1);

namespace Kameleoon\Network;

final class FetchedConfiguration
{
    public ?string $configuration;
    public ?string $lastModified;

    public function __construct(?string $configuration, ?string $lastModified)
    {
        $this->configuration = $configuration;
        $this->lastModified = $lastModified;
    }

    public function __toString(): string
    {
        return "FetchedConfiguration{" .
            "configuration:" . strlen($this->configuration ?? "") .
            ",lastModified:'" . $this->lastModified .
            "'}";
    }
}
