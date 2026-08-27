<?php

declare(strict_types=1);

namespace Kameleoon\Events;

/**
 * Describes an update of the SDK configuration (data file).
 */
final class DataFileUpdateEvent
{
    /**
     * The data file was updated by fetching the configuration from the server.
     */
    public const SOURCE_POLLING = "POLLING";

    private string $source;
    private int $dateModified;

    /**
     * @internal
     */
    public function __construct(string $source, int $dateModified)
    {
        $this->source = $source;
        $this->dateModified = $dateModified;
    }

    /**
     * Returns the source of the update, one of the `SOURCE_*` constants.
     */
    public function getSource(): string
    {
        return $this->source;
    }

    /**
     * Returns the modification date of the data file as a Unix timestamp in milliseconds.
     */
    public function getDateModified(): int
    {
        return $this->dateModified;
    }

    public function __toString(): string
    {
        return sprintf("DataFileUpdateEvent{Source:%s,DateModified:%d}", $this->source, $this->dateModified);
    }
}
