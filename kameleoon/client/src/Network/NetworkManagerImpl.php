<?php

declare(strict_types=1);

namespace Kameleoon\Network;

use Kameleoon\Helpers\SdkVersion;
use Kameleoon\Network\AccessToken\AccessTokenSource;
use Kameleoon\Network\AccessToken\AccessTokenSourceFactory;

class NetworkManagerImpl implements NetworkManager
{
    public const USER_AGENT_HEADER_NAME = "User-Agent";
    public const HEADER_SDK_TYPE = "X-Kameleoon-SDK-Type";
    public const HEADER_SDK_VERSION = "X-Kameleoon-SDK-Version";

    const GRANT_TYPE = "client_credentials";
    const H_CONTENT_TYPE_NAME = "Content-Type";
    const H_CONTENT_TYPE_VALUE = "application/x-www-form-urlencoded";
    const H_AUTHORIZATION = "Authorization";

    const NETWORK_CALL_FAILED_FMT = "Network call '%s' failed";

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

    protected function makeSyncCall(SyncRequest $request)
    {
        $request->timeout = $this->getTimeout($request->timeout);
        $accessToken = $this->applyAccessToken($request, $request->timeout);
        $response = $this->netProvider->callSync($request);
        if ($response->error !== null) {
            $errMsg = sprintf(self::NETWORK_CALL_FAILED_FMT, $request->url);
            error_log("{$errMsg}}: Error occurred during request: {$response->error}");
        } elseif (intdiv($response->code, 100) !== 2) {
            $errMsg = sprintf(self::NETWORK_CALL_FAILED_FMT, $request->url);
            error_log("{$errMsg}: Received unexpected status code '{$response->code}'");
            if (($response->code == 401 || $response->code == 403) && $accessToken !== null) {
                $request->isJwtRequired = false;
                $this->netProvider->callSync($request);
                $this->accessTokenSource->discardToken($accessToken);
            }
        } else {
            return $response->body;
        }
        return null;
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

    public function getRemoteVisitorData(string $visitorCode, ?int $timeout = null)
    {
        $url = $this->urlProvider->makeVisitorDataGetUrl($visitorCode);
        $request = new SyncRequest(Request::GET, $url, null, $timeout, ResponseContentType::JSON, true);
        return $this->makeSyncCall($request);
    }

    public function sendTrackingData(string $visitorCode, iterable $lines, ?string $userAgent, bool $debug): void
    {
        $url = $this->urlProvider->makeTrackingUrl($visitorCode);
        if ($debug) {
            $debugParams = $this->urlProvider->makeExperimentRegisterDebugParams();
            if ($debugParams !== null) {
                $url .= $debugParams;
            }
        }
        $data = $this->formTrackingCallData($lines);
        $headers = ($userAgent !== null) ? [self::USER_AGENT_HEADER_NAME => $userAgent] : null;
        $request = new AsyncRequest($url, $headers, $data, true);
        $this->makeAsyncCall($request);
    }

    protected function makeAsyncCall(AsyncRequest $request)
    {
        $this->applyAccessToken($request, $this->defaultTimeout);
        $this->netProvider->callAsync($request);
    }

    private function formTrackingCallData(iterable $lines): string
    {
        $data = "";
        foreach ($lines as $line) {
            if (!empty($data)) {
                $data .= "\n";
            }
            $data .= $line->getQuery();
        }
        return $data;
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
