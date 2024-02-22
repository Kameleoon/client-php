<?php

namespace Kameleoon\Network\AccessToken;

use Kameleoon\Network\NetworkManager;

class AccessTokenSourceFactoryImpl implements AccessTokenSourceFactory
{
    private string $clientId;
    private string $clientSecret;
    private string $kameleoonWorkDir;

    public function __construct(string $clientId, string $clientSecret, string $kameleoonWorkDir)
    {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->kameleoonWorkDir = $kameleoonWorkDir;
    }

    public function create(NetworkManager $networkManager): AccessTokenSource
    {
        return new AccessTokenSourceImpl(
            $this->clientId,
            $this->clientSecret,
            $this->kameleoonWorkDir,
            $networkManager
        );
    }
}
