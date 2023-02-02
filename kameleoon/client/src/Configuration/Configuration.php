<?php

namespace Kameleoon\Configuration;

use Exception;

class Configuration
{
    public static function parse($json)
    {
        $configurations = (object) array();

        $configurations->experiments = self::createFromJSON($json->experiments, "id", Experiment::class);
        $configurations->featureFlags = self::createFromJSON($json->featureFlags, "id", FeatureFlag::class);
        $configurations->featureFlagsV2 = self::createFromJSON($json->featureFlagConfigurations, "featureKey", FeatureFlagV2::class);

        return $configurations;
    }

    private static function createFromJSON($json, $key, $class) {
        $arrayObj = array();
        try {
            foreach ($json as $obj) {
                $arrayObj[$obj->$key] = new $class($obj);
            }
        } catch (Exception $e) {
            return NULL;
        }
        return $arrayObj;
    }
}

?>
