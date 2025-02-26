<?php

declare(strict_types=1);

namespace Kameleoon\Network;

use Kameleoon\Network\AccessToken\AccessTokenSourceFactory;

interface NetworkManagerFactory
{
    public function create(
        string $siteCode,
        ?string $environment,
        int $defaultTimeout,
        string $kameleoonWorkDir,
        AccessTokenSourceFactory $accessTokenSourceFactory,
        ?string $networkDomain
    ): NetworkManager;
}
