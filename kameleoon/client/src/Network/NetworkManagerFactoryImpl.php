<?php

declare(strict_types=1);

namespace Kameleoon\Network;

class NetworkManagerFactoryImpl implements NetworkManagerFactory
{
    public function create(string $siteCode, ?string $environment,
        int $defaultTimeout, string $kameleoonWorkDir): NetworkManager
    {
        $urlProvider = new UrlProvider($siteCode, UrlProvider::DEFAULT_DATA_API_URL);
        $netProvider = new NetProviderImpl($kameleoonWorkDir);
        return new NetworkManagerImpl($urlProvider, $environment, $defaultTimeout, $netProvider);
    }
}
