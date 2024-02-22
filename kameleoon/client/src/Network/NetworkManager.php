<?php

declare(strict_types=1);

namespace Kameleoon\Network;

use Kameleoon\Network\AccessToken\AccessTokenSource;
use Kameleoon\Network\NetProvider;

interface NetworkManager
{
    public function getUrlProvider(): UrlProvider;
    public function getEnvironment(): ?string;
    public function getDefaultTimeout(): int;
    public function getNetProvider(): NetProvider;
    public function getAccessTokenSource(): AccessTokenSource;

    // Automation API
    public function fetchAccessJWToken(string $clientId, string $clientSecret, ?int $timeout = null): ?object;

    // SDK config API
    public function fetchConfiguration(?int $timeout = null): ?string;

    // Data API
    public function getRemoteData(string $key, ?int $timeout = null);
    public function getRemoteVisitorData(string $visitorCode, ?int $timeout = null);
    public function sendTrackingData(string $visitorCode, iterable $lines, ?string $userAgent, bool $debug): void;
}
