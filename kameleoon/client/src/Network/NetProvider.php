<?php

declare(strict_types=1);

namespace Kameleoon\Network;

interface NetProvider
{
    public function get(GetRequest $request): Response;
    public function post(PostRequest $request): string;
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
    public string $url;
    public ?array $headers;

    public function __construct(string $url, ?array $headers)
    {
        $this->url = $url;
        $this->headers = $headers;
    }
}

class GetRequest extends Request
{
    public int $timeout;
    public int $responseContentType;

    public function __construct(string $url, ?array $headers, int $timeout, int $responseContentType)
    {
        parent::__construct($url, $headers);
        $this->timeout = $timeout;
        $this->responseContentType = $responseContentType;
    }
}

class PostRequest extends Request
{
    public string $data;

    public function __construct(string $url, ?array $headers, string $data)
    {
        parent::__construct($url, $headers);
        $this->data = $data;
    }
}
