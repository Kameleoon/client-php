<?php

declare(strict_types=1);

namespace Kameleoon\Data;

class ApplicationVersion implements Data
{
    private string $version;

    /**
     * @param string $version Application version (semantic versioning: major, major.minor, or major.minor.patch)
     */
    public function __construct(string $version)
    {
        $this->version = $version;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function __toString(): string
    {
        return "ApplicationVersion{version:'$this->version'}";
    }
}
