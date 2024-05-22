<?php

declare(strict_types=1);

namespace Kameleoon\Configuration;

use Exception;
use Kameleoon\Exception\FeatureEnvironmentDisabled;
use Kameleoon\Exception\FeatureNotFound;

class DataFile
{
    private array $featureFlags;
    private Settings $settings;
    private ?string $environment;
    private bool $hasAnyTDRule;
    private array $featureFlagById;
    private array $ruleBySegmentId;
    private array $variationById;
    private CustomDataInfo $customDataInfo;

    private bool $isLoaded = false;

    public function __construct(object $jsonDataFile, ?string $environment = null)
    {
        $this->environment = $environment;
        $this->featureFlags = self::createFromJSON($jsonDataFile->featureFlags, "featureKey", FeatureFlag::class);
        $this->settings = new Settings($jsonDataFile);
        $this->customDataInfo = new CustomDataInfo($jsonDataFile->customData ?? null);
    }

    public function setLoaded()
    {
        $this->isLoaded = true;
    }

    public function isLoaded()
    {
        return $this->isLoaded;
    }

    public function getFeatureFlags(): array
    {
        return $this->featureFlags;
    }

    public function getSettings(): Settings
    {
        return $this->settings;
    }

    public function getCustomDataInfo(): CustomDataInfo
    {
        return $this->customDataInfo;
    }

    public function hasAnyTargetedDeliveryRule(): bool
    {
        if (!isset($this->hasAnyTDRule)) {
            $this->hasAnyTDRule = $this->detIfHasAnyTargetedDeliveryRule();
        }
        return $this->hasAnyTDRule;
    }

    public function getFeatureFlagById(int $featureFlagId): ?FeatureFlag
    {
        if (!isset($this->featureFlagById)) {
            $this->featureFlagById = $this->collectFeatureFlagById();
        }
        return $this->featureFlagById[$featureFlagId] ?? null;
    }

    public function getRuleBySegmentId(int $segmentId): ?Rule
    {
        if (!isset($this->ruleBySegmentId)) {
            $this->ruleBySegmentId = $this->collectRuleBySegmentId();
        }
        return $this->ruleBySegmentId[$segmentId] ?? null;
    }

    public function getVariation(int $variationId): ?VariationByExposition
    {
        if (!isset($this->variationById)) {
            $this->variationById = $this->collectVariationById();
        }
        return $this->variationById[$variationId] ?? null;
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

    private function detIfHasAnyTargetedDeliveryRule(): bool
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

    private function collectFeatureFlagById(): array
    {
        $featureFlagById = [];
        foreach ($this->featureFlags as $ffKey => $ff) {
            $featureFlagById[$ff->id] = $ff;
        }
        return $featureFlagById;
    }

    private function collectRuleBySegmentId(): array
    {
        $ruleBySegmentId = [];
        foreach ($this->featureFlags as $ffKey => $ff) {
            foreach ($ff->rules as $ruleKey => $rule) {
                $ruleBySegmentId[intval($rule->getSegment()->id ?? null)] = $rule;
            }
        }
        return $ruleBySegmentId;
    }

    private function collectVariationById(): array
    {
        $variationById = [];
        foreach ($this->featureFlags as $ffKey => $ff) {
            foreach ($ff->rules as $ruleKey => $rule) {
                foreach ($rule->variationByExposition as $varKey => $variation) {
                    $variationById[$variation->variationId] = $variation;
                }
            }
        }
        return $variationById;
    }
}
