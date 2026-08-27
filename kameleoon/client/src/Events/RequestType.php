<?php

declare(strict_types=1);

namespace Kameleoon\Events;

/**
 * Type of an HTTP request performed by the SDK.
 */
final class RequestType
{
    /**
     * Fetching of the SDK configuration (data file).
     */
    public const DATAFILE = "DATAFILE";

    /**
     * Sending of tracking data.
     */
    public const TRACKING = "TRACKING";

    /**
     * Fetching of remote visitor data.
     */
    public const REMOTE_VISITOR_DATA = "REMOTE_VISITOR_DATA";

    /**
     * Fetching of remote data.
     */
    public const REMOTE_DATA = "REMOTE_DATA";

    /**
     * Fetching of an access token.
     */
    public const ACCESS_TOKEN = "ACCESS_TOKEN";

    private function __construct()
    {
    }
}
