<?php

declare(strict_types=1);

namespace Kameleoon\Network;

interface NetProvider
{
    public function callSync(SyncRequest $request): Response;
    // There is no async request in PHP SDK. This type of requests will be be performed with background daemon.
    public function callAsync(AsyncRequest $request): string;
}

final class ResponseContentType
{
    public const NONE = 0;
    public const TEXT = 1;
    public const JSON = 2;
}

class Response
{
    public $error;
    public ?int $code;
    public $body;

    public function __construct($error, ?int $code, $body)
    {
        $this->error = $error;
        $this->code = $code;
        $this->body = $body;
    }
}

abstract class Request
{
    const GET = "GET";
    const POST = "POST";

    public string $httpMethod;
    public string $url;
    public ?array $headers;
    public ?string $body;
    public bool $isJwtRequired;

    public function __construct(
        string $httpMethod,
        string $url,
        ?array $headers,
        bool $isJwtRequired = false,
        ?string $body = null
    ) {
        $this->httpMethod = $httpMethod;
        $this->url = $url;
        $this->headers = $headers;
        $this->body = $body;
        $this->isJwtRequired = $isJwtRequired;
    }
}

class SyncRequest extends Request
{
    public ?int $timeout;
    public int $responseContentType;

    public function __construct(
        string $httpMethod,
        string $url,
        ?array $headers,
        ?int $timeout,
        int $responseContentType,
        bool $isJwtRequired = false,
        ?string $body = null
    ) {
        parent::__construct($httpMethod, $url, $headers, $isJwtRequired, $body);
        $this->timeout = $timeout;
        $this->responseContentType = $responseContentType;
    }
}

// There is no async request in PHP SDK. This type of requests will be be performed with background daemon.
class AsyncRequest extends Request
{
    public function __construct(string $url, ?array $headers, string $body, bool $isJwtRequired = false)
    {
        parent::__construct(Request::POST, $url, $headers, $isJwtRequired, $body);
    }
}
