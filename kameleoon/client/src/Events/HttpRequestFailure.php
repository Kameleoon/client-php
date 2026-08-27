<?php

declare(strict_types=1);

namespace Kameleoon\Events;

/**
 * Describes a failure of an HTTP request performed by the SDK.
 */
final class HttpRequestFailure
{
    /**
     * The request completed with an unexpected HTTP status code.
     */
    public const REASON_HTTP_STATUS = "HTTP_STATUS";

    /**
     * The request failed with an error or exception (e.g. a network error).
     */
    public const REASON_EXCEPTION = "EXCEPTION";

    /**
     * The request was cancelled (for example, due to a timeout).
     */
    public const REASON_CANCELLED = "CANCELLED";

    private string $reason;
    private ?int $httpStatus;

    /**
     * @var mixed Usually a `Throwable` or an error message string.
     */
    private $cause;

    private function __construct(string $reason, ?int $httpStatus, $cause)
    {
        $this->reason = $reason;
        $this->httpStatus = $httpStatus;
        $this->cause = $cause;
    }

    /**
     * @internal
     */
    public static function httpStatus(int $httpStatus): self
    {
        return new self(self::REASON_HTTP_STATUS, $httpStatus, null);
    }

    /**
     * @internal
     * @param mixed $cause A `Throwable` or an error message string.
     */
    public static function exception($cause): self
    {
        return new self(self::REASON_EXCEPTION, null, $cause);
    }

    /**
     * @internal
     */
    public static function cancelled(): self
    {
        return new self(self::REASON_CANCELLED, null, null);
    }

    /**
     * Returns the failure reason, one of the `REASON_*` constants.
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Returns the HTTP status code if the reason is `REASON_HTTP_STATUS`, otherwise `null`.
     */
    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    /**
     * Returns the failure cause (usually a `Throwable` or an error message string)
     * if the reason is `REASON_EXCEPTION`, otherwise `null`.
     *
     * @return mixed
     */
    public function getCause()
    {
        return $this->cause;
    }

    public function __toString(): string
    {
        return sprintf(
            "HttpRequestFailure{Reason:%s,HttpStatus:%s,Cause:%s}",
            $this->reason,
            $this->httpStatus ?? "null",
            is_object($this->cause) ? get_class($this->cause) : ($this->cause ?? "null")
        );
    }
}
