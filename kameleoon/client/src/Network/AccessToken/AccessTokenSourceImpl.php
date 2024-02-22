<?php

namespace Kameleoon\Network\AccessToken;

use Kameleoon\Network\NetworkManager;

class AccessTokenSourceImpl implements AccessTokenSource
{
    const TOKEN_EXPIRATION_GAP = 60 * 10; // in seconds
    const JWT_ACCESS_TOKEN_FIELD = "access_token";
    const JWT_EXPIRES_IN_FIELD = "expires_in";
    const JWT_EXPIRES_AT_FIELD = "expires_at";
    const ACCESS_TOKEN_FILE = "access_token.json";

    private string $clientId;
    private string $clientSecret;
    private string $accessTokenFilePath;
    private NetworkManager $networkManager;

    private ?string $cachedToken;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $kameleoonWorkDir,
        NetworkManager $networkManager
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->accessTokenFilePath = $kameleoonWorkDir . self::ACCESS_TOKEN_FILE;
        $this->networkManager = $networkManager;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function getAccessFileTokenPath(): string
    {
        return $this->accessTokenFilePath;
    }

    public function getNetworkManager(): NetworkManager
    {
        return $this->networkManager;
    }

    public function getToken(?int $timeout = null): ?string
    {
        if (isset($this->cachedToken) && $this->cachedToken !== null) {
            return $this->cachedToken;
        }
        if (file_exists($this->accessTokenFilePath)) {
            $token = $this->loadToken(file_get_contents($this->accessTokenFilePath, true));
            if ($token !== null) {
                $this->cachedToken = $token;
                return $token;
            }
        }
        return $this->fetchToken($timeout);
    }

    private function fetchToken(?int $timeout = null): ?string
    {
        if (!file_exists($this->accessTokenFilePath)) {
            $fp = fopen($this->accessTokenFilePath, "a");
            fclose($fp);
        }
        $token = null;
        if (($fp = fopen($this->accessTokenFilePath, "r+")) && flock($fp, LOCK_EX)) {
            $token = $this->loadToken(stream_get_contents($fp));
            if ($token === null) {
                $tokenResponse =
                    $this->networkManager->fetchAccessJWToken($this->clientId, $this->clientSecret, $timeout);
                if ($tokenResponse !== null) {
                    $this->saveToken($fp, $tokenResponse);
                    $token = $tokenResponse->{self::JWT_ACCESS_TOKEN_FIELD};
                } else {
                    ftruncate($fp, 0);
                }
            }
            $this->cachedToken = $token;
            flock($fp, LOCK_UN);
            fclose($fp);
        }
        return $token;
    }

    private function loadToken(string $tokenInFile): ?string
    {
        $accessTokenJson = json_decode($tokenInFile);
        if ($accessTokenJson === null) {
            return null;
        }
        $expiredAt = $accessTokenJson->{self::JWT_EXPIRES_AT_FIELD};
        if (time() < $expiredAt) {
            return $accessTokenJson->{self::JWT_ACCESS_TOKEN_FIELD};
        }
        return null;
    }

    private function saveToken($fp, object $tokenResponse)
    {
        if ($tokenResponse->{SELF::JWT_EXPIRES_IN_FIELD} < self::TOKEN_EXPIRATION_GAP) {
            error_log(
                sprintf(
                    "Kameleoon SDK: Access token life time (%ds) is not long enough to cache the token",
                    $tokenResponse->{SELF::JWT_EXPIRES_IN_FIELD}
                )
            );
        }
        $tokenResponse->{SELF::JWT_EXPIRES_AT_FIELD} = time() + $tokenResponse->{SELF::JWT_EXPIRES_IN_FIELD};
        unset($tokenResponse->{SELF::JWT_EXPIRES_IN_FIELD});
        if ($jsonEncode = json_encode($tokenResponse)) {
            rewind($fp);
            fwrite($fp, $jsonEncode);
            fflush($fp);
            ftruncate($fp, ftell($fp));
        }
    }

    public function discardToken(string $token)
    {
        if (($fp = fopen($this->accessTokenFilePath, "r+")) && flock($fp, LOCK_EX)) {
            $tokenFile = $this->loadToken(stream_get_contents($fp));
            if ($token == $tokenFile) {
                ftruncate($fp, 0);
                $this->cachedToken = null;
            }
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
}
