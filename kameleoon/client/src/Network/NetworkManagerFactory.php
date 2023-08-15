<?php

declare(strict_types=1);

namespace Kameleoon\Network;

interface NetworkManagerFactory
{
    public function create(string $siteCode, ?string $environment,
        int $defaultTimeout, string $kameleoonWorkDir): NetworkManager;
}
