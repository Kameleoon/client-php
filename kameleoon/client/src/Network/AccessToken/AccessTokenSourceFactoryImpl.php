<?php

namespace Kameleoon\Network\AccessToken;

use Kameleoon\Helpers\StringHelper;
use Kameleoon\Logging\KameleoonLogger;
use Kameleoon\Network\NetworkManager;

class AccessTokenSourceFactoryImpl implements AccessTokenSourceFactory
{
    private string $clientId;
    private string $clientSecret;
    private string $kameleoonWorkDir;

    public function __construct(string $clientId, string $clientSecret, string $kameleoonWorkDir)
    {
        KameleoonLogger::debug(function () use ($clientId, $clientSecret, $kameleoonWorkDir) {
            return sprintf("CALL: new AccessTokenSourceFactory(clientId: '%s', clientSecret: '%s', kameleoonWorkDir: '%s')",
                StringHelper::secret($clientId), StringHelper::secret($clientSecret), $kameleoonWorkDir);
        });
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->kameleoonWorkDir = $kameleoonWorkDir;
        KameleoonLogger::debug(function () use ($clientId, $clientSecret, $kameleoonWorkDir) {
            return sprintf("RETURN: new AccessTokenSourceFactory(clientId: '%s', clientSecret: '%s', kameleoonWorkDir: '%s')",
                StringHelper::secret($clientId), StringHelper::secret($clientSecret), $kameleoonWorkDir);
        });
    }

    public function create(NetworkManager $networkManager): AccessTokenSource
    {
        KameleoonLogger::debug("CALL: AccessTokenSourceFactory->create(networkManager)");
        $source = new AccessTokenSourceImpl(
            $this->clientId,
            $this->clientSecret,
            $this->kameleoonWorkDir,
            $networkManager
        );
        KameleoonLogger::debug("RETURN: AccessTokenSourceFactory->create(networkManager) -> (source)");
        return $source;
    }
}
