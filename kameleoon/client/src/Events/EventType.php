<?php

declare(strict_types=1);

namespace Kameleoon\Events;

/**
 * SDK event types which can be handled with `KameleoonClient::setEventHandler`.
 */
final class EventType
{
    /**
     * HTTP request event. Requires a `HttpRequestHandler` handler.
     */
    public const HTTP_REQUEST = "HTTP_REQUEST";

    /**
     * Data file update event. Requires a `DataFileUpdateHandler` handler.
     */
    public const DATAFILE_UPDATE = "DATAFILE_UPDATE";

    private const HANDLER_TYPES = [
        self::HTTP_REQUEST => HttpRequestHandler::class,
        self::DATAFILE_UPDATE => DataFileUpdateHandler::class,
    ];

    /**
     * @internal
     */
    public static function getHandlerType(string $eventType): ?string
    {
        return self::HANDLER_TYPES[$eventType] ?? null;
    }

    private function __construct() {}
}
