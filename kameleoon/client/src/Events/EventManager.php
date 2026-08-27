<?php

declare(strict_types=1);

namespace Kameleoon\Events;

use Kameleoon\Logging\KameleoonLogger;
use Throwable;

/**
 * @internal
 */
final class EventManager
{
    /**
     * @var array<string, EventHandler>
     */
    private array $eventHandlers = [];

    public function setEventHandler(string $eventType, ?EventHandler $handler): void
    {
        $handlerType = EventType::getHandlerType($eventType);
        if ($handlerType === null) {
            KameleoonLogger::error("Unknown event type '%s'", $eventType);
            return;
        }
        if (($handler !== null) && !($handler instanceof $handlerType)) {
            KameleoonLogger::error(
                "Handler for event type '%s' must be an instance of %s",
                $eventType,
                $handlerType
            );
            return;
        }
        if ($handler === null) {
            unset($this->eventHandlers[$eventType]);
        } else {
            $this->eventHandlers[$eventType] = $handler;
        }
    }

    public function fireHttpRequestSucceeded(string $requestType, int $httpStatus, int $durationMillis): void
    {
        $handler = $this->eventHandlers[EventType::HTTP_REQUEST] ?? null;
        if ($handler instanceof HttpRequestHandler) {
            try {
                $handler->onRequestSucceeded($requestType, $httpStatus, $durationMillis);
            } catch (Throwable $ex) {
                KameleoonLogger::warning("HTTP request event handler failed: %s", $ex->getMessage());
            }
        }
    }

    public function fireHttpRequestFailed(string $requestType, HttpRequestFailure $failure, int $durationMillis): void
    {
        $handler = $this->eventHandlers[EventType::HTTP_REQUEST] ?? null;
        if ($handler instanceof HttpRequestHandler) {
            try {
                $handler->onRequestFailed($requestType, $failure, $durationMillis);
            } catch (Throwable $ex) {
                KameleoonLogger::warning("HTTP request event handler failed: %s", $ex->getMessage());
            }
        }
    }

    public function fireDataFileUpdate(DataFileUpdateEvent $event): void
    {
        $handler = $this->eventHandlers[EventType::DATAFILE_UPDATE] ?? null;
        if ($handler instanceof DataFileUpdateHandler) {
            try {
                $handler->onUpdate($event);
            } catch (Throwable $ex) {
                KameleoonLogger::warning("Data file update event handler failed: %s", $ex->getMessage());
            }
        }
    }
}
