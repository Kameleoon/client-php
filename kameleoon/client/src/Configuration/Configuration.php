<?php

namespace Kameleoon\Configuration;

use Exception;

class Configuration
{
    public static function parse($json)
    {
        $configurations = (object) array();

        $configurations->experiments = self::createFromJSON($json->experiments, "id", Experiment::class);
        $configurations->featureFlags = self::createFromJSON(
            $json->featureFlagConfigurations,
            "featureKey",
            FeatureFlag::class
        );

        return $configurations;
    }

    private static function createFromJSON($json, $key, $class)
    {
        $arrayObj = array();
        try {
            foreach ($json as $obj) {
                $arrayObj[$obj->$key] = new $class($obj);
            }
        } catch (Exception $e) {
            return null;
        }
        return $arrayObj;
    }
}
