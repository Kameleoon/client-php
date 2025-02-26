<?php

declare(strict_types=1);

namespace Kameleoon\Network;

use Kameleoon\Network\AccessToken\AccessTokenSourceFactory;

class NetworkManagerFactoryImpl implements NetworkManagerFactory
{
    public function create(
        string $siteCode,
        ?string $environment,
        int $defaultTimeout,
        string $kameleoonWorkDir,
        AccessTokenSourceFactory $accessTokenSourceFactory,
        ?string $networkDomain
    ): NetworkManager {
        $urlProvider = new UrlProvider($siteCode, $networkDomain);
        $netProvider = new NetProviderImpl($siteCode, $kameleoonWorkDir);
        return new NetworkManagerImpl(
            $urlProvider,
            $environment,
            $defaultTimeout,
            $netProvider,
            $accessTokenSourceFactory
        );
    }
}
