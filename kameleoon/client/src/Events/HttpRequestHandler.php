<?php

declare(strict_types=1);

namespace Kameleoon\Events;

/**
 * Handler of SDK HTTP request events. Called once per actual HTTP request attempt, including retries.
 */
interface HttpRequestHandler extends EventHandler
{
    /**
     * Called when an HTTP request attempt succeeds.
     *
     * @param string $requestType The type of the request, one of the `RequestType` constants.
     * @param int $httpStatus The HTTP status code of the response.
     * @param int $durationMillis The duration of the request attempt in milliseconds.
     */
    public function onRequestSucceeded(string $requestType, int $httpStatus, int $durationMillis): void;

    /**
     * Called when an HTTP request attempt fails.
     *
     * @param string $requestType The type of the request, one of the `RequestType` constants.
     * @param HttpRequestFailure $failure The description of the failure.
     * @param int $durationMillis The duration of the request attempt in milliseconds.
     */
    public function onRequestFailed(string $requestType, HttpRequestFailure $failure, int $durationMillis): void;
}
