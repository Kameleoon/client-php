<?php

namespace Kameleoon;

use Kameleoon\Hybrid\HybridManagerImpl;
use Kameleoon\Storage\VariationStorageImpl;
use Kameleoon\Network\NetworkManagerFactoryImpl;

class KameleoonClientFactory
{
    private $clients = [];

    public static function create($siteCode, $configurationFilePath = "/etc/kameleoon/client-php.json")
    {
        if (!in_array($siteCode, self::getInstance()->clients)) {
            self::getInstance()->clients[$siteCode] = new KameleoonClientImpl(
                $siteCode,
                $configurationFilePath,
                new VariationStorageImpl(),
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
