<?php

declare(strict_types=1);

namespace Kameleoon;

use Kameleoon\Data\Manager\VisitorManagerImpl;
use Kameleoon\Hybrid\HybridManagerImpl;
use Kameleoon\Network\NetworkManagerFactoryImpl;

class KameleoonClientFactory
{
    private array $clients = [];

    public static function create(
        string $siteCode,
        ?string $configurationFilePath = "/etc/kameleoon/client-php.json"
    ) {
        if (!in_array($siteCode, self::getInstance()->clients)) {
            $kameleoonConfig = KameleoonClientConfig::readFromFile($configurationFilePath);
            return KameleoonClientFactory::createWithConfig(
                $siteCode,
                $kameleoonConfig
            );
        }
        return self::getInstance()->clients[$siteCode];
    }

    public static function createWithConfig(
        string $siteCode,
        KameleoonClientConfig $kameleoonConfig
    ) {
        if (!in_array($siteCode, self::getInstance()->clients)) {
            self::getInstance()->clients[$siteCode] = new KameleoonClientImpl(
                $siteCode,
                $kameleoonConfig,
                new VisitorManagerImpl(),
                new HybridManagerImpl(),
                new NetworkManagerFactoryImpl()
            );
        }
        return self::getInstance()->clients[$siteCode];
    }

    public static function forget($siteCode)
    {
        unset(self::getInstance()->clients[$siteCode]);
    }

    private static $instance = null;

    private static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new KameleoonClientFactory();
        }

        return self::$instance;
    }
}
