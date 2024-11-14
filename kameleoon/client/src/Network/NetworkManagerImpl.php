<?php

declare(strict_types=1);

namespace Kameleoon\Network;

use Kameleoon\Helpers\SdkVersion;
use Kameleoon\Logging\KameleoonLogger;
use Kameleoon\Network\AccessToken\AccessTokenSource;
use Kameleoon\Network\AccessToken\AccessTokenSourceFactory;
use Kameleoon\Types\RemoteVisitorDataFilter;

class NetworkManagerImpl implements NetworkManager
{
    public const USER_AGENT_HEADER_NAME = "User-Agent";
    public const HEADER_SDK_TYPE = "X-Kameleoon-SDK-Type";
    public const HEADER_SDK_VERSION = "X-Kameleoon-SDK-Version";

    const GRANT_TYPE = "client_credentials";
    const H_CONTENT_TYPE_NAME = "Content-Type";
    const H_CONTENT_TYPE_VALUE = "application/x-www-form-urlencoded";
    const H_AUTHORIZATION = "Authorization";

    private UrlProvider $urlProvider;
    private ?string $environment;
    private int $defaultTimeout;
    private NetProvider $netProvider;
    private AccessTokenSource $accessTokenSource;

    public function __construct(
        UrlProvider $urlProvider,
        ?string $environment,
        int $defaultTimeout,
        NetProvider $netProvider,
        AccessTokenSourceFactory $accessTokenSourceFactory
    ) {
        $this->urlProvider = $urlProvider;
        $this->environment = $environment;
        $this->defaultTimeout = $defaultTimeout;
        $this->netProvider = $netProvider;
        $this->accessTokenSource = $accessTokenSourceFactory->create($this);
    }

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

    public function getAccessTokenSource(): AccessTokenSource
    {
        return $this->accessTokenSource;
    }

    private function getTimeout(?int $timeout): int
    {
        return ($timeout === null) ? $this->defaultTimeout : $timeout;
    }

    protected function makeSyncCall(SyncRequest $request, bool &$success = false)
    {
        KameleoonLogger::debug("Running request %s", $request);
        $request->timeout = $this->getTimeout($request->timeout);
        $accessToken = $this->applyAccessToken($request, $request->timeout);
        $response = $this->netProvider->callSync($request);
        if ($response->error !== null) {
            KameleoonLogger::error("%s call '%s' failed: Error occurred during request: %s",
                $request->httpMethod, $request->url, $response->error);
        } elseif (!$response->isExpectedStatusCode()) {
            KameleoonLogger::error("%s call '%s' failed: Received unexpected status code '%s'",
                $request->httpMethod, $request->url, $response->code);
            if (($response->code == 401) && ($accessToken !== null)) {
                $request->isJwtRequired = false;
                $this->netProvider->callSync($request);
                $this->accessTokenSource->discardToken($accessToken);
            }
        } else {
            KameleoonLogger::debug("Fetched response %s for request %s", $response, $request);
            $success = true;
            return $response->body;
        }
        KameleoonLogger::debug("Fetched response null for request %s", $request);
        $success = false;
        return null;
    }

    protected function makeAsyncCall(AsyncRequest $request): void
    {
        $this->applyAccessToken($request, $this->defaultTimeout);
        $this->netProvider->callAsync($request);
    }

    private function applyAccessToken(Request $request, ?int $timeout): ?string
    {
        $token = null;
        if ($request->isJwtRequired) {
            $token = $this->accessTokenSource->getToken($timeout);
            if ($token !== null) {
                $request->headers[self::H_AUTHORIZATION] = "Bearer " . $token;
            }
        }
        return $token;
    }

    public function fetchConfiguration(?int $timeout = null): ?string
    {
        $url = $this->urlProvider->makeConfigurationUrl($this->environment);
        $headers = [
            self::HEADER_SDK_TYPE => SdkVersion::SDK_NAME,
            self::HEADER_SDK_VERSION => SdkVersion::getVersion()
        ];
        $request = new SyncRequest(Request::GET, $url, $headers, $timeout, ResponseContentType::TEXT);
        return $this->makeSyncCall($request);
    }

    public function getRemoteData(string $key, ?int $timeout = null)
    {
        $url = $this->urlProvider->makeApiDataGetRequestUrl($key);
        $request = new SyncRequest(Request::GET, $url, null, $timeout, ResponseContentType::JSON, true);
        return $this->makeSyncCall($request);
    }

    public function getRemoteVisitorData(string $visitorCode, RemoteVisitorDataFilter $filter, bool $isUniqueIdentifier,
        ?int $timeout = null)
    {
        $url = $this->urlProvider->makeVisitorDataGetUrl($visitorCode, $filter, $isUniqueIdentifier);
        $request = new SyncRequest(Request::GET, $url, null, $timeout, ResponseContentType::JSON, true);
        return $this->makeSyncCall($request);
    }

    public function sendTrackingData(string $lines, bool $debug): void
    {
        $url = $this->urlProvider->makeTrackingUrl();
        if ($debug) {
            $debugParams = $this->urlProvider->makeExperimentRegisterDebugParams();
            if ($debugParams !== null) {
                $url .= $debugParams;
            }
        }
        $headers = ["Content-Type" => "text/plain"];
        $request = new AsyncRequest($url, $headers, $lines, true);
        $this->makeAsyncCall($request);
    }

    public function sendTrackingDataInstantly(string $lines, bool $debug, ?int $timeout = null): bool
    {
        $url = $this->urlProvider->makeTrackingUrl();
        if ($debug) {
            $debugParams = $this->urlProvider->makeExperimentRegisterDebugParams();
            if ($debugParams !== null) {
                $url .= $debugParams;
            }
        }
        $headers = ["Content-Type" => "text/plain"];
        $request = new SyncRequest(
            Request::POST, $url, $headers, $timeout, ResponseContentType::NONE, true, $lines,
        );
        $success = false;
        $this->makeSyncCall($request, $success);
        return $success;
    }

    public function fetchAccessJWToken(string $clientId, string $clientSecret, ?int $timeout = null): ?object
    {
        $url = $this->urlProvider->makeAccessTokenUrl();
        $data = $this->formAccessJWTTokenCall($clientId, $clientSecret);
        $request = new SyncRequest(
            Request::POST,
            $url,
            [self::H_CONTENT_TYPE_NAME => self::H_CONTENT_TYPE_VALUE],
            $timeout,
            ResponseContentType::JSON,
            false,
            $data
        );
        return $this->makeSyncCall($request);
    }

    private function formAccessJWTTokenCall(string $clientId, string $clientSecret): string
    {
        return (string)new QueryBuilder(
            new QueryParam(QueryParams::GRANT_TYPE, self::GRANT_TYPE),
            new QueryParam(QueryParams::CLIENT_ID, $clientId),
            new QueryParam(QueryParams::CLIENT_SECRET, $clientSecret)
        );
    }
}
