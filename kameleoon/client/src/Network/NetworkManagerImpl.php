<?php

declare(strict_types=1);

namespace Kameleoon\Network;

use Kameleoon\Data\UserAgent;

class NetworkManagerImpl implements NetworkManager
{
    public const USER_AGENT_HEADER_NAME = "User-Agent";

    private UrlProvider $urlProvider;
    private ?string $environment;
    private int $defaultTimeout;
    private NetProvider $netProvider;

    public function getUrlProvider(): UrlProvider
    {
        return $this->urlProvider;
    }
    public function getEnvironment(): ?string
    {
        return $this->environment;
    }
    public function getDefaultTimeout(): int
    {
        return $this->defaultTimeout;
    }
    public function getNetProvider(): NetProvider
    {
        return $this->netProvider;
    }

    public function __construct(
        UrlProvider $urlProvider,
        ?string $environment,
        int $defaultTimeout,
        NetProvider $netProvider
    ) {
        $this->urlProvider = $urlProvider;
        $this->environment = $environment;
        $this->defaultTimeout = $defaultTimeout;
        $this->netProvider = $netProvider;
    }

    private function getTimeout(?int $timeout): int
    {
        return ($timeout === null) ? $this->defaultTimeout : $timeout;
    }

    private function makeGetCall(GetRequest $request)
    {
        $errMsg = "GET call '{$request->url}' failed";
        $response = $this->netProvider->get($request);
        if ($response->error !== null) {
            error_log("{$errMsg}: Error occurred during request: {$response->error}");
        } elseif (intdiv($response->code, 100) !== 2) {
            error_log("{$errMsg}: Received unexpected status code '{$response->code}'");
        } else {
            return $response->body;
        }
        return null;
    }

    public function fetchConfiguration(?int $timeout = null): ?string
    {
        $url = $this->urlProvider->makeConfigurationUrl($this->environment);
        $request = new GetRequest($url, null, $this->getTimeout($timeout), ResponseContentType::TEXT);
        return $this->makeGetCall($request);
    }

    public function getRemoteData(string $key, ?int $timeout = null)
    {
        $url = $this->urlProvider->makeApiDataGetRequestUrl($key);
        $request = new GetRequest($url, null, $this->getTimeout($timeout), ResponseContentType::JSON);
        return $this->makeGetCall($request);
    }

    public function getRemoteVisitorData(string $visitorCode, ?int $timeout = null)
    {
        $url = $this->urlProvider->makeVisitorDataGetUrl($visitorCode);
        $request = new GetRequest($url, null, $this->getTimeout($timeout), ResponseContentType::JSON);
        return $this->makeGetCall($request);
    }

    public function sendTrackingData(string $visitorCode, array $lines, ?UserAgent $userAgent, bool $debug): void
    {
        $url = $this->urlProvider->makeTrackingUrl($visitorCode);
        if ($debug) {
            $debugParams = $this->urlProvider->makeExperimentRegisterDebugParams();
            if ($debugParams !== null) {
                $url .= $debugParams;
            }
        }
        $data = $this->formTrackingCallData($lines);
        $headers = ($userAgent !== null) ? [self::USER_AGENT_HEADER_NAME => $userAgent->getValue()] : null;
        $request = new PostRequest($url, $headers, $data);
        $this->netProvider->post($request);
    }
    private function formTrackingCallData(array $lines): string
    {
        $data = "";
        foreach ($lines as $line) {
            $textLine = $line->obtainFullPostTextLine();
            if ($textLine != null) {
                if (!empty($data)) {
                    $data .= "\n";
                }
                $data .= $textLine;
            }
        }
        return $data;
    }
}
