<?php

declare(strict_types=1);

namespace Kameleoon\Configuration;

use Exception;
use Kameleoon\Exception\FeatureEnvironmentDisabled;
use Kameleoon\Exception\FeatureNotFound;
use Kameleoon\Logging\KameleoonLogger;

class DataFile
{
    private array $featureFlags;
    private array $meGroups;
    private Settings $settings;
    private ?string $environment;
    private bool $hasAnyTDRule;
    private array $featureFlagById;
    private array $ruleBySegmentId;
    private array $ruleInfoByExpId;
    private array $variationById;
    private CustomDataInfo $customDataInfo;
    private array $experimentIdsWithJsCssVariable;
    private ?Experiment $holdout;

    public function __construct(object $jsonDataFile, ?string $environment = null)
    {
        KameleoonLogger::debug(
            "CALL: new DataFile(jsonDataFile: %s, environment: '%s')", $jsonDataFile, $environment);
        $this->environment = $environment;
        $this->featureFlags = self::createFromJSON($jsonDataFile->featureFlags, "featureKey", FeatureFlag::class);
        $this->meGroups = self::makeMEGroups($this->featureFlags);
        $this->settings = new Settings($jsonDataFile);
        $this->customDataInfo = new CustomDataInfo($jsonDataFile->customData ?? null);
        $this->holdout = is_object($jsonDataFile->holdout ?? null) ? new Experiment($jsonDataFile->holdout) : null;
        KameleoonLogger::debug(
            "RETURN: new DataFile(jsonDataFile: %s, environment: '%s')", $jsonDataFile, $environment);
    }

    public function getFeatureFlags(): array
    {
        return $this->featureFlags;
    }

    public function &getMEGroups(): array
    {
        return $this->meGroups;
    }

    public function getSettings(): Settings
    {
        return $this->settings;
    }

    public function getCustomDataInfo(): CustomDataInfo
    {
        return $this->customDataInfo;
    }

    public function getHoldout(): ?Experiment
    {
        return $this->holdout;
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
        KameleoonLogger::debug("CALL: DataFile->getFeatureFlagById(featureFlagId: %s)", $featureFlagId);
        if (!isset($this->featureFlagById)) {
            $this->featureFlagById = $this->collectFeatureFlagById();
        }
        $featureFlag = $this->featureFlagById[$featureFlagId] ?? null;
        KameleoonLogger::debug("RETURN: DataFile->getFeatureFlagById(featureFlagId: %s) -> (featureFlag: %s)",
            $featureFlagId, $featureFlag);
        return $featureFlag;
    }

    public function getRuleBySegmentId(int $segmentId): ?Rule
    {
        KameleoonLogger::debug("CALL: DataFile->getRuleBySegmentId(segmentId: %s)", $segmentId);
        if (!isset($this->ruleBySegmentId)) {
            $this->ruleBySegmentId = $this->collectRuleBySegmentId();
        }
        $rule = $this->ruleBySegmentId[$segmentId] ?? null;
        KameleoonLogger::debug("RETURN: DataFile->getRuleBySegmentId(segmentId: %s) -> (rule: %s)",
            $segmentId, $rule);
        return $rule;
    }

    public function getRuleInfoByExpId(int $experimentId): ?RuleInfo
    {
        KameleoonLogger::debug("CALL: DataFile->getRuleInfoByExpId(experimentId: %s)", $experimentId);
        if (!isset($this->ruleInfoByExpId)) {
            $this->ruleInfoByExpId = $this->collectRuleInfoByExpId();
        }
        $ruleInfo = $this->ruleInfoByExpId[$experimentId] ?? null;
        KameleoonLogger::debug("RETURN: DataFile->getRuleInfoByExpId(experimentId: %s) -> (ruleInfo: %s)",
            $experimentId, $ruleInfo);
        return $ruleInfo;
    }

    public function getVariation(int $variationId): ?VariationByExposition
    {
        KameleoonLogger::debug("CALL: DataFile->getVariation(variationId: %s)", $variationId);
        if (!isset($this->variationById)) {
            $this->variationById = $this->collectVariationById();
        }
        $variation = $this->variationById[$variationId] ?? null;
        KameleoonLogger::debug("RETURN: DataFile->getVariation(variationId: %s) -> (featureFlag: %s)",
            $variationId, $variation);
        return $variation;
    }

    public function hasExperimentJsCssVariable(int $experimentId): bool
    {
        if (!isset($this->experimentIdsWithJsCssVariable)) {
            $this->experimentIdsWithJsCssVariable = $this->collectExperimentIdsWithJsCssVariable();
        }
        return array_key_exists($experimentId, $this->experimentIdsWithJsCssVariable);
    }

    private static function createFromJSON($json, $key, $class): array
    {
        $arrayObj = array();
        try {
            foreach ($json as $obj) {
                $arrayObj[$obj->$key] = new $class($obj);
            }
        } catch (Exception $e) {
            KameleoonLogger::error("Data file configuration cannot be parsed: " . $e->getMessage());
            return [];
        }
        return $arrayObj;
    }

    public function getFeatureFlag(string $featureKey): FeatureFlag
    {
        KameleoonLogger::debug("CALL: DataFile->getFeatureFlag(featureKey: '%s')", $featureKey);
        $featureFlag = $this->featureFlags[$featureKey] ?? null;
        if ($featureFlag === null) {
            throw new FeatureNotFound("Feature {$featureKey} not found");
        }
        if (!$featureFlag->getEnvironmentEnabled()) {
            $environment = $this->environment ?? 'default';
            throw new FeatureEnvironmentDisabled("Feature '{$featureKey}' is disabled for {$environment} environment");
        }
        KameleoonLogger::debug("RETURN: DataFile->getFeatureFlag(featureKey: '%s') -> (featureFlag: %s)",
            $featureKey, $featureFlag);
        return $featureFlag;
    }

    private static function makeMEGroups(array $featureFlags): array
    {
        $meGroupLists = [];
        foreach ($featureFlags as $featureFlag) {
            if ($featureFlag->meGroupName !== null) {
                if (array_key_exists($featureFlag->meGroupName, $meGroupLists)) {
                    $meGroupLists[$featureFlag->meGroupName][] = $featureFlag;
                } else {
                    $meGroupLists[$featureFlag->meGroupName] = [$featureFlag];
                }
            }
        }
        $meGroups = [];
        foreach ($meGroupLists as $meGroupName => $meGroupList) {
            $meGroups[$meGroupName] = new MEGroup($meGroupList);
        }
        return $meGroups;
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

    private function collectRuleInfoByExpId(): array
    {
        $ruleInfoByExpId = [];
        foreach ($this->featureFlags as $ffKey => $ff) {
            foreach ($ff->rules as $ruleKey => $rule) {
                $ruleInfoByExpId[$rule->experiment->id] = new RuleInfo($ff, $rule);
            }
        }
        return $ruleInfoByExpId;
    }

    private function collectVariationById(): array
    {
        $variationById = [];
        foreach ($this->featureFlags as $ffKey => $ff) {
            foreach ($ff->rules as $ruleKey => $rule) {
                foreach ($rule->experiment->variationsByExposition as $varKey => $variation) {
                    $variationById[$variation->variationId] = $variation;
                }
            }
        }
        return $variationById;
    }

    private function collectExperimentIdsWithJsCssVariable(): array
    {
        $experimentIdsWithJSOrCSS = [];
        foreach ($this->featureFlags as $featureFlag) {
            $hasFeatureFlagVariableJsCss = $this->hasFeatureFlagVariableJsCss($featureFlag);
            foreach ($featureFlag->rules as $rule) {
                if ($hasFeatureFlagVariableJsCss) {
                    $experimentIdsWithJSOrCSS[$rule->experiment->id] = true;
                }
            }
        }
        return $experimentIdsWithJSOrCSS;
    }

    private function hasFeatureFlagVariableJsCss(FeatureFlag $featureFlag): bool
    {
        $variations = $featureFlag->getVariations();
        if (!empty($variations)) {
            $firstVariation = $variations[0];
            foreach ($firstVariation->variables as $variable) {
                if ($variable->type === "JS" || $variable->type === "CSS") {
                    return true;
                }
            }
        }
        return false;
    }

    public function __toString(): string
    {
        return sprintf(
            "DataFile{environment:'%s',featureFlags:%d,settings:%s}",
            $this->environment,
            count($this->featureFlags),
            (string) $this->settings,
        );
    }
}
