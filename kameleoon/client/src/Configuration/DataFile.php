<?php

declare(strict_types=1);

namespace Kameleoon\Configuration;

use Exception;
use Kameleoon\Exception\FeatureEnvironmentDisabled;
use Kameleoon\Exception\FeatureNotFound;

class DataFile
{
    public array $featureFlags;
    public Settings $settings;
    private ?string $environment;

    private bool $isLoaded = false;

    public function __construct(object $jsonDataFile, ?string $environment = null)
    {
        $this->environment = $environment;
        $this->featureFlags = self::createFromJSON($jsonDataFile->featureFlags, "featureKey", FeatureFlag::class);
        $this->settings = new Settings($jsonDataFile);
    }

    public function setLoaded()
    {
        $this->isLoaded = true;
    }

    public function isLoaded()
    {
        return $this->isLoaded;
    }

    private static function createFromJSON($json, $key, $class): array
    {
        $arrayObj = array();
        try {
            foreach ($json as $obj) {
                $arrayObj[$obj->$key] = new $class($obj);
            }
        } catch (Exception $e) {
            error_log("Data file configuration cannot be parsed: " . $e->getMessage());
            return [];
        }
        return $arrayObj;
    }

    public function getFeatureFlag(string $featureKey): FeatureFlag
    {
        $featureFlag = $this->featureFlags[$featureKey] ?? null;
        if ($featureFlag === null) {
            throw new FeatureNotFound("Feature {$featureKey} not found");
        }
        if (!$featureFlag->getEnvironmentEnabled()) {
            $environment = $this->environment ?? 'default';
            throw new FeatureEnvironmentDisabled("Feature '{$featureKey}' is disabled for {$environment} environment");
        }
        return $featureFlag;
    }

    public function hasAnyTargetedDeliveryRule(): bool
    {
        foreach ($this->featureFlags as $featureFlag) {
            if ($featureFlag->getEnvironmentEnabled()) {
                foreach ($featureFlag->rules as $rule) {
                    if ($rule->isTargetedDelivery()) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
}
