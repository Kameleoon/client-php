<?php

namespace Kameleoon;

use Exception;
use Kameleoon\Data\UserAgent;
use Kameleoon\Exception\ExperimentConfigurationNotFound;
use Kameleoon\Exception\FeatureConfigurationNotFound;
use Kameleoon\Exception\NotAllocated;
use Kameleoon\Exception\NotTargeted;
use Kameleoon\Exception\VariationConfigurationNotFound;
use Kameleoon\Exception\InvalidArgumentException;
use Kameleoon\Exception\SiteCodeDisabled;
use Kameleoon\Exception\VisitorCodeNotValid;
use Kameleoon\Targeting\TargetingData;

use Kameleoon\Targeting\Condition\CustomDatum;
use Kameleoon\Targeting\Condition\ExclusiveExperiment;
use Kameleoon\Targeting\Condition\TargetedExperiment;
use Kameleoon\Targeting\Condition\BrowserCondition;
use Kameleoon\Targeting\Condition\ConversionCondition;
use Kameleoon\Targeting\Condition\DeviceCondition;
use Kameleoon\Targeting\Condition\PageTitleCondition;
use Kameleoon\Targeting\Condition\PageUrlCondition;
use Kameleoon\Targeting\Condition\SdkInfo;
use Kameleoon\Targeting\Condition\SdkLanguageCondition;
use Kameleoon\Targeting\Condition\VisitorCodeCondition;
use Kameleoon\Targeting\TargetingEngine;

use Kameleoon\Configuration\Configuration;
use Kameleoon\Configuration\Experiment;
use Kameleoon\Configuration\FeatureFlag;
use Kameleoon\Configuration\Rule;
use Kameleoon\Configuration\Variation;
use Kameleoon\Configuration\VariationByExposition;
use Kameleoon\Exception\ConfigurationNotLoaded;
use Kameleoon\Exception\FeatureVariableNotFound;
use Kameleoon\Helpers\SdkVersion;
use Kameleoon\Hybrid\HybridManager;
use Kameleoon\Network\ActivityEvent;
use Kameleoon\Network\ExperimentEvent;
use Kameleoon\Storage\VariationStorage;
use Kameleoon\Network\NetworkManager;
use Kameleoon\Network\NetworkManagerFactory;

class KameleoonClientImpl implements KameleoonClient
{
    const DEFAULT_TIMEOUT_MILLISECONDS = 5000;
    const SECONDS_BETWEEN_CONFIGURATION_UPDATE = 3600;
    const HEXADECIMAL_ALPHABET = "0123456789ABCDEF";
    const NONCE_BYTE_LENGTH = 8;
    const DEFAULT_KAMELEOON_WORK_DIR = "/tmp/kameleoon/php-client/";
    const FILE_CONFIGURATION_NAME = "kameleoon-configuration-";
    const VISITOR_CODE_MAX_LENGTH = 255;

    private $configurations;
    private $unsentData;
    private $configurationFilePath;
    private $commonConfiguration;
    private $visitorCode;
    private $debugMode = false;
    private $refreshInterval;
    private $kameleoonWorkDir;
    private $targetingData;
    private $configurationLoaded;
    private $cookieOptions;
    private $environment = null;
    private $userAgentData = null;
    private $defaultTimeout = null;

    private VariationStorage $variationStorage;
    private HybridManager $hybridManager;
    private NetworkManager $networkManager;

    public function __construct(
        string $siteCode,
        string $configurationFilePath,
        VariationStorage $variationStorage,
        HybridManager $hybridManager,
        NetworkManagerFactory $networkManagerFactory
    ) {
        $this->configurations = (object) array(
            "experiments" => array(),
            "featureFlags" => array()
        );
        $this->unsentData = array();
        $this->targetingData = array();
        $this->configurationLoaded = false;
        $this->userAgentData = array();

        $this->variationStorage = $variationStorage;
        $this->hybridManager = $hybridManager;

        $this->commonConfiguration = ConfigurationParser::parse($configurationFilePath);
        $this->kameleoonWorkDir = isset($this->commonConfiguration["kameleoon_work_dir"]) ?
            $this->commonConfiguration["kameleoon_work_dir"] : self::DEFAULT_KAMELEOON_WORK_DIR;
        $this->configurationFilePath = $this->kameleoonWorkDir . self::FILE_CONFIGURATION_NAME .
            $siteCode . ".json";
        $this->refreshInterval = isset($this->commonConfiguration["actions_configuration_refresh_interval"]) ?
            $this->commonConfiguration["actions_configuration_refresh_interval"] * 60 :
            self::SECONDS_BETWEEN_CONFIGURATION_UPDATE;
        $this->defaultTimeout = $this->commonConfiguration["default_timeout"] ?? self::DEFAULT_TIMEOUT_MILLISECONDS;
        if (isset($this->commonConfiguration["debug_mode"])) {
            $this->debugMode = $this->commonConfiguration["debug_mode"] == "true";
        }
        $this->cookieOptions = isset($this->commonConfiguration["cookie_options"]) ?
            $this->commonConfiguration["cookie_options"] : array();

        if (!is_dir($this->kameleoonWorkDir)) {
            mkdir($this->kameleoonWorkDir, 0755, true);
        }
        $this->environment = isset($this->commonConfiguration["environment"]) ?
            $this->commonConfiguration["environment"] : null;

        $this->networkManager = $networkManagerFactory->create(
            $siteCode,
            $this->environment,
            $this->defaultTimeout,
            $this->kameleoonWorkDir
        );
    }

    /*
     * API Methods (Public Methods)
     */

    public function triggerExperiment(string $visitorCode, int $experimentID, ?int $timeout = null)
    {
        $this->validateVisitorCode($visitorCode);
        $this->loadConfiguration($timeout);
        $xpConf = $this->configurations->experiments[$experimentID] ?? null;
        if ($xpConf === null) {
            throw new ExperimentConfigurationNotFound("Experiment configuration {$experimentID} isn't found");
        }
        $this->checkSiteCodeEnable($xpConf);
        $targeted = $this->checkTargeting($visitorCode, $experimentID, $xpConf);
        if ($targeted) {
            // Disable searching in variation storage (uncommented if you need use variation storage)
            // chech for saved variation for rule if it's experimentation rule
            // $savedVariation = $this->variationStorage->getSavedVariation($visitorCode, $xpConf);
            // if (!is_null($savedVariation)) {
            //     $noneVariation = false;
            //     $variationId = $savedVariation;
            // } else {
            $variationId = $this->calculateVariationForExperiment($visitorCode, $xpConf);
            $noneVariation = $variationId === null;
            $variationId = $variationId ?? 0; //~ Need to send reference (0) if variation isn't found
            $this->saveVariation($visitorCode, $experimentID, $variationId);
            // }
        } else {
            $variationId = null;
            $noneVariation = null;
        }
        $this->sendTrackingRequest($visitorCode, $experimentID, $variationId);
        if (!$targeted) {
            throw new NotTargeted("Visitor {$visitorCode} isn't targeted for experiment {$experimentID}");
        }
        if ($noneVariation) {
            throw new NotAllocated("Visitor {$visitorCode} isn't allocated for experiment {$experimentID}");
        }
        return $variationId;
    }

    /**
     * @deprecated deprecated since version 3.0.0. Please use `isFeatureActive`
     */
    public function activateFeature(string $visitorCode, string $featureKey, ?int $timeout = null): bool
    {
        error_log("Deprecated: `activateFeature` is deprecated. Please use `isFeatureActive`instead ");
        return $this->isFeatureActive($visitorCode, $featureKey, $timeout);
    }

    public function getFeatureVariationKey(string $visitorCode, string $featureKey, ?int $timeout = null): string
    {
        [, $variationKey] = $this->getFeatureVariationKeyInternal($visitorCode, $featureKey, $timeout);
        return $variationKey;
    }

    public function getFeatureVariable(
        string $visitorCode,
        string $featureKey,
        string $variableName,
        ?int $timeout = null
    ) {
        [$featureFlag, $variationKey] = $this->getFeatureVariationKeyInternal($visitorCode, $featureKey, $timeout);
        $variation = $featureFlag->getVariation($variationKey);
        if (is_null($variation)) {
            return null;
        }
        $variable = $variation->getVariable($variableName);
        if (!is_null($variable)) {
            return $variable->getValue();
        } else {
            throw new FeatureVariableNotFound("Feature variable {$variableName} not found");
        }
    }

    public function isFeatureActive(string $visitorCode, string $featureKey, ?int $timeout = null): bool
    {
        [$ff, $variationKey] = $this->getFeatureVariationKeyInternal($visitorCode, $featureKey, $timeout);
        return $variationKey != Variation::VARIATION_OFF;
    }

    public function trackConversion($visitorCode, int $goalID, $revenue = 0.0)
    {
        $this->addData($this->validateVisitorCode($visitorCode), new Data\Conversion($goalID));
        $this->flush($visitorCode);
    }

    public function addData($visitorCode, ...$data)
    {
        $this->validateVisitorCode($visitorCode);
        $this->getUnsentData($visitorCode);
        if (!isset($this->targetingData[$visitorCode])) {
            $this->targetingData[$visitorCode] = array();
        }
        foreach ($data as $datum) {
            if ($datum instanceof UserAgent) {
                $this->addUserAgent($visitorCode, $datum);
            } else {
                array_push($this->unsentData[$visitorCode], $datum);
                array_push($this->targetingData[$visitorCode], new TargetingData($datum));
            }
        }
    }

    public function flush($visitorCode = null)
    {
        if ($visitorCode !== null) {
            $this->validateVisitorCode($visitorCode);
            $this->sendTrackingRequest($visitorCode);
        } else {
            foreach ($this->getUnsentUsers() as $user) {
                $this->flush($user);
            }
        }
    }

    /**
     * @deprecated deprecated since version 3.0.0. Please use `getVisitorCode`
     */
    public function obtainVisitorCode($topLevelDomain, $visitorCode = null)
    {
        error_log("Deprecated: `obtainVisitorCode` is deprecated. Please use `getVisitorCode`instead ");
        return $this->getVisitorCode($topLevelDomain, $visitorCode);
    }

    // Here you must provide your own base domain, eg mydomain.com
    public function getVisitorCode($topLevelDomain = null, $visitorCode = null)
    {
        if (isset($this->visitorCode)) {
            return $this->visitorCode;
        } else {
            if (isset($_COOKIE["kameleoonVisitorCode"])) {
                $value = $_COOKIE["kameleoonVisitorCode"];
                if (strpos($value, "_js_") !== false) {
                    $visitorCode = substr($value, 4);
                } else {
                    $visitorCode = $value;
                }
            }
            if (is_null($visitorCode)) {
                $alphabet = "abcdefghijklmnopqrstuvwxyz0123456789";
                $alphabetLength = strlen($alphabet);
                $visitorCode = "";
                for ($i = 0; $i < 16; $i++) {
                    $randomNumber = floor((mt_rand() / mt_getrandmax()) * $alphabetLength);
                    $visitorCode .= substr($alphabet, $randomNumber, 1);
                }
            }

            $secure = false;
            $httpOnly = false;
            $samesite = "Lax";
            $domain = null;

            if (isset($this->cookieOptions["secure"])) {
                $secure = $this->cookieOptions["secure"];
            }
            if (isset($this->cookieOptions["http_only"])) {
                $httpOnly = $this->cookieOptions["http_only"];
            }
            if (isset($this->cookieOptions["samesite"])) {
                $samesite = $this->cookieOptions["samesite"];
            }
            if (!is_null($topLevelDomain)) {
                $domain = $topLevelDomain;
            } elseif (isset($this->cookieOptions["domain"])) {
                $domain = $this->cookieOptions["domain"];
            } else {
                throw new InvalidArgumentException('Domain is required');
            }

            if (version_compare(phpversion(), '7.3', '<')) {
                setcookie("kameleoonVisitorCode", $visitorCode, time() + 32832000, "/", $domain, $secure, $httpOnly);
            } else {
                $cookie_options = array(
                    "expires" => time() + 32832000,
                    "path" => '/',
                    "domain" => $domain,
                    "secure" => $secure,
                    "httponly" => $httpOnly,
                    "samesite" => $samesite
                );
                setcookie("kameleoonVisitorCode", $visitorCode, $cookie_options);
            }
            $this->visitorCode = (string) $visitorCode;
            return $this->validateVisitorCode($visitorCode);
        }
    }

    /**
     * @deprecated deprecated since version 3.0.0. Please use `getVariationAssociatedData`
     */
    public function obtainVariationAssociatedData(int $variationId, ?int $timeout = null)
    {
        error_log("Deprecated: `obtainVariationAssociatedData` is deprecated. " .
            "Please use `getVariationAssociatedData`instead ");
        return $this->getVariationAssociatedData($variationId, $timeout);
    }

    public function getVariationAssociatedData(int $variationId, ?int $timeout = null)
    {
        //check that configuration is loaded
        $this->loadConfiguration($timeout);

        $result = null;
        foreach ($this->configurations->experiments as $experiment) {
            foreach ($experiment->variationConfigurations as $vid => $variationConfiguration) {
                if ($vid == $variationId || strval($vid) == $variationId) {
                    $result = $variationConfiguration->customJson;
                }
            }
        }
        if ($result === null) {
            throw new VariationConfigurationNotFound("Variation configuration not found");
        }
        return $result;
    }

    public function getRemoteData(string $key, ?int $timeout = null)
    {
        $data = $this->networkManager->getRemoteData($key, $timeout);
        if ($data === null) {
            error_log("Get remote data failed");
        }
        return $data;
    }

    public function retrieveDataFromRemoteSource(string $key, ?int $timeout = null)
    {
        return $this->getRemoteData($key, $timeout);
    }

    public function getFeatureAllVariables(string $featureKey, string $variationKey, ?int $timeout = null): array
    {
        $this->loadConfiguration($timeout);

        $featureFlag = $this->findFeatureFlag($featureKey);
        $variation = $featureFlag->getVariation($variationKey);
        if (is_null($variation)) {
            throw new VariationConfigurationNotFound("Variation key {$variationKey} not found");
        }
        return array_map(fn ($var) => $var->getValue(), $variation->variables);
    }

    public function getExperimentList(?int $timeout = null): array
    {
        $this->loadConfiguration($timeout);
        return array_keys($this->configurations->experiments);
    }

    public function getExperimentListForVisitor(
        string $visitorCode,
        bool $onlyAllocated = true,
        ?int $timeout = null
    ): array {
        $this->loadConfiguration($timeout);
        $this->validateVisitorCode($visitorCode);
        $arrayIds = array();
        foreach ($this->configurations->experiments as $experiment) {
            if (
                $this->checkTargeting($visitorCode, $experiment->id, $experiment) &&
                (!$onlyAllocated || !is_null($this->calculateVariationForExperiment($visitorCode, $experiment)))
            ) {
                array_push($arrayIds, $experiment->id);
            }
        }
        return $arrayIds;
    }

    public function getFeatureList(?int $timeout = null): array
    {
        $this->loadConfiguration($timeout);
        return array_keys($this->configurations->featureFlags);
    }

    public function getActiveFeatureListForVisitor(string $visitorCode, ?int $timeout = null): array
    {
        $this->validateVisitorCode($visitorCode);
        $arrayKeys = array();
        $this->loadConfiguration($timeout);
        foreach ($this->configurations->featureFlags as $featureFlag) {
            [$variation, $rule] = $this->calculateVariationKeyForFeature($visitorCode, $featureFlag);
            $variationKey = $this->calculateVariationKey($variation, $rule, $featureFlag->defaultVariationKey);
            if ($variationKey != Variation::VARIATION_OFF) {
                array_push($arrayKeys, $featureFlag->featureKey);
            }
        }
        return $arrayKeys;
    }

    public function getEngineTrackingCode(string $visitorCode): string
    {
        $visitorVariationStorage = $this->variationStorage->getSavedVariations($visitorCode);
        return $this->hybridManager->getEngineTrackingCode($visitorVariationStorage);
    }

    /*
     * Helper Methods (Private Methods)
     */

    private function getFeatureVariationKeyInternal(
        string $visitorCode,
        string $featureKey,
        ?int $timeout = null
    ): array {
        $this->loadConfiguration($timeout);
        $this->validateVisitorCode($visitorCode);
        $featureFlag = $this->findFeatureFlag($featureKey);
        [$variation, $rule] = $this->calculateVariationKeyForFeature($visitorCode, $featureFlag);
        $variationKey = $this->calculateVariationKey($variation, $rule, $featureFlag->defaultVariationKey);
        $experimentId = ($rule !== null) ? $rule->experimentId : null;
        $variationId = ($variation !== null) ? $variation->variationId ?? 0 : null;
        $this->sendTrackingRequest($visitorCode, $experimentId, $variationId);
        $this->saveVariation($visitorCode, $experimentId, $variationId);
        return [$featureFlag, $variationKey];
    }

    private function calculateVariationKey(
        ?VariationByExposition $varByExp,
        ?Rule $rule,
        string $defaultVariationKey
    ): string {
        if ($varByExp != null) {
            return $varByExp->variationKey;
        }
        if ($rule != null && $rule->type == Rule::EXPERIMENTATION) {
            return Variation::VARIATION_OFF;
        }
        return $defaultVariationKey;
    }

    private function saveVariation(
        string $visitorCode,
        ?int $experimentId,
        ?int $variationId
    ) {
        if (!is_null($experimentId) && !is_null($variationId)) {
            $this->variationStorage->saveVariation(
                $visitorCode,
                $experimentId,
                $variationId
            );
        }
    }

    //load configuration if it was not loaded
    private function loadConfiguration(?int $timeout = null)
    {
        $needUpdateByRefreshInterval = file_exists($this->configurationFilePath) &&
            (time() >= filemtime($this->configurationFilePath) + $this->refreshInterval);
        if (!$this->configurationLoaded || $needUpdateByRefreshInterval) {
            $timeout = $timeout ?? $this->defaultTimeout;
            if (file_exists($this->configurationFilePath)) {
                $fp = fopen($this->configurationFilePath, "r+");
                if (!$needUpdateByRefreshInterval || !flock($fp, LOCK_EX)) {
                    try {
                        $obj = json_decode(file_get_contents($this->configurationFilePath, true));
                        $this->configurations = Configuration::parse($obj);
                    } catch (Exception $e) {
                        if (!flock($fp, LOCK_EX)) {
                            $this->updateConfiguration($timeout);
                            flock($fp, LOCK_UN);
                        }
                    }
                } else {
                    $this->updateConfiguration($timeout);
                    flock($fp, LOCK_UN);
                }
            } else {
                $this->updateConfiguration($timeout);
            }
        }
        $this->configurationLoaded = true;
    }

    // check targeting
    private function checkTargeting($visitorCode, $containerID, $xpOrFFOrRule)
    {
        $targeting = true;

        // performing targeting
        $targetingSegment = $xpOrFFOrRule->getTargetingSegment();
        if (null != $targetingSegment) {
            $targetingTree = $targetingSegment->getTargetingTree();
            // obtaining targeting checking result and assigning targeting to container
            $targeting = TargetingEngine::checkTargetingTree(
                $targetingTree,
                function ($type) use ($visitorCode, $containerID) {
                    return $this->getConditionData($type, $visitorCode, $containerID);
                }
            );
        }

        return $targeting;
    }

    private function getConditionData($type, $visitorCode, $campaignId)
    {
        switch ($type) {
            case CustomDatum::TYPE:
            case PageUrlCondition::TYPE:
            case PageTitleCondition::TYPE:
            case DeviceCondition::TYPE:
            case BrowserCondition::TYPE:
            case ConversionCondition::TYPE:
                return $this->getTargetingData($visitorCode);

            case VisitorCodeCondition::TYPE:
                return $visitorCode;

            case TargetedExperiment::TYPE:
                return $this->variationStorage->getSavedVariations($visitorCode);

            case ExclusiveExperiment::TYPE:
                return [$campaignId, $this->variationStorage->getSavedVariations($visitorCode)];

            case SdkLanguageCondition::TYPE:
                return new SdkInfo(SdkVersion::getName(), SdkVersion::getVersion());

            default:
                return null;
        }
    }

    private function obtainHashDouble(string $visitorCode, int $containerID, array $variationConfigurations)
    {
        return $this->obtainHashDoubleCommon($visitorCode, $containerID, $variationConfigurations);
    }

    private function obtainHashDoubleRule(string $visitorCode, int $containerID, ?int $respoolTime)
    {
        $suffix = '';
        if (!is_null($respoolTime)) {
            $suffix = (string) $respoolTime;
        }
        return $this->obtainHashDoubleCommon($visitorCode, $containerID, array(), $suffix);
    }

    private function obtainHashDoubleCommon(
        string $visitorCode,
        int $containerID,
        array $variationConfigurations,
        string $suffix = ''
    ) {
        $respoolTimes = array();
        foreach ($variationConfigurations as $v) {
            if ($v->respoolTime != null) {
                array_push($respoolTimes, $v->respoolTime);
            }
        }
        return floatval(intval(substr(hash("sha256", $visitorCode . $containerID . $suffix . join("", $respoolTimes)), 0, 8), 16) / pow(2, 32));
    }

    public static function obtainNonce() //~ Why this method is in KameleoonClient?
    {
        $alphabetLength = strlen(self::HEXADECIMAL_ALPHABET);
        $result = "";

        for ($i = 0; $i < self::NONCE_BYTE_LENGTH * 2; $i++) {
            $randomNumber = floor((mt_rand() / mt_getrandmax()) * $alphabetLength);
            $result .= substr(self::HEXADECIMAL_ALPHABET, $randomNumber, 1);
        }

        return $result;
    }

    private function updateConfiguration($timeout)
    {
        try {
            $configurationOutput = $this->networkManager->fetchConfiguration(null, $timeout);
            $configuration = json_decode($configurationOutput);
            if (
                isset($configuration->experiments) &&
                isset($configuration->featureFlagConfigurations)
            ) {
                file_put_contents($this->configurationFilePath, $configurationOutput);
                $this->configurations = Configuration::parse($configuration);
            } else {
                $configuration = json_decode(file_get_contents($this->configurationFilePath, true));
                $this->configurations = Configuration::parse($configuration);
            }
        } catch (Exception $e) {
            throw new ConfigurationNotLoaded("Configuration file is not loaded: " . $e->getMessage());
        } finally {
            $this->updateConfigurationFileModificationTime();
        }
    }

    private function updateConfigurationFileModificationTime()
    {
        if (file_exists($this->configurationFilePath)) {
            touch($this->configurationFilePath);
            clearstatcache();
        }
    }

    private function getUnsentUsers()
    {
        return array_keys($this->unsentData);
    }

    private function getUnsentData($visitorCode)
    {
        if (!array_key_exists($visitorCode, $this->unsentData)) {
            $this->unsentData[$visitorCode] = array();
        }
        return $this->unsentData[$visitorCode];
    }

    private function popUnsentData($visitorCode)
    {
        if (array_key_exists($visitorCode, $this->unsentData)) {
            $data = $this->unsentData[$visitorCode];
            unset($this->unsentData[$visitorCode]);
            return $data;
        }
        return array();
    }

    private function getTargetingData($visitorCode)
    {
        if (!isset($this->targetingData[$visitorCode])) {
            $this->targetingData[$visitorCode] = array();
        }
        return $this->targetingData[$visitorCode];
    }

    private function validateVisitorCode($visitorCode)
    {
        if (isset($visitorCode) == false || strlen($visitorCode) == 0) {
            throw new VisitorCodeNotValid("Visitor code is empty");
        } elseif (strlen($visitorCode) > self::VISITOR_CODE_MAX_LENGTH) {
            throw new VisitorCodeNotValid("Visitor max length is " . self::VISITOR_CODE_MAX_LENGTH . " characters");
        } else {
            return $visitorCode;
        }
    }

    private function checkSiteCodeEnable($expOrFF)
    {
        if (!$expOrFF->isSiteCodeEnabled) {
            $siteCode = $this->networkManager->getUrlProvider()->getSiteCode();
            throw new SiteCodeDisabled("Sitecode '" . $siteCode . "' disabled");
        }
    }

    private function checkFeatureIdIsString($featureId)
    {
        if (gettype($featureId) != "string") {
            error_log("Please use `featureId` with type of `string`. This is necessary to support multi-environment feature. Supporting of `int` type will be removed in next releases");
        }
    }

    private function addUserAgent(string $visitorCode, UserAgent $userAgent)
    {
        $this->userAgentData[$visitorCode] = $userAgent;
    }

    private function getUserAgent(string $visitorCode): ?UserAgent
    {
        if (isset($this->userAgentData[$visitorCode])) {
            return $this->userAgentData[$visitorCode];
        } else {
            return NULL;
        }
    }

    private function findFeatureFlag(string $featureKey): FeatureFlag
    {
        if (isset($this->configurations->featureFlags[$featureKey])) {
            return $this->configurations->featureFlags[$featureKey];
        } else {
            throw new FeatureConfigurationNotFound("Feature {$featureKey} not found");
        }
    }

    private function calculateVariationKeyForFeature(string $visitorCode, FeatureFlag $featureFlag): array
    {
        // no rules -> return defaultVariationKey
        foreach ($featureFlag->rules as $rule) {
            // check if visitor is targeted for rule, else next rule
            if ($this->checkTargeting($visitorCode, $featureFlag->id, $rule)) {
                // uses for rule exposition
                $hashRule = $this->obtainHashDoubleRule($visitorCode, $rule->id, $rule->respoolTime);
                // check main expostion for rule with hashRule
                if ($hashRule <= $rule->exposition) {
                    if (
                        $rule->type == Rule::TARGETED_DELIVERY &&
                        count($rule->variationByExposition) > 0
                    ) {
                        return [$rule->variationByExposition[0], $rule];
                    }
                    // uses for variation's expositions
                    $hashVariation = $this->obtainHashDoubleRule(
                        $visitorCode,
                        $rule->experimentId,
                        $rule->respoolTime
                    );
                    // get variation key with new hashVariation
                    $variation = $this->calculateVariatonRuleHash($rule, $hashVariation);
                    // variation can be null for experiment rules only, for targeted rule will be always exist
                    if (!is_null($variation)) {
                        return [$variation, $rule];
                    }
                } elseif ($rule->type == Rule::TARGETED_DELIVERY) {
                    // if visitor is targeted but not bucketed for targeted rule then break cycle -> return default
                    break;
                }
            }
        }
        return [null, null];
    }

    private function calculateVariatonRuleHash(Rule $rule, float $hash): ?VariationByExposition
    {
        $total = 0.0;
        foreach ($rule->variationByExposition as $variationByExposition) {
            $total += $variationByExposition->exposition;
            if ($total >= $hash) {
                return $variationByExposition;
            }
        }
        return null;
    }

    private function sendTrackingRequest(
        string $visitorCode,
        ?int $experimentId = null,
        ?int $variationId = null
    ): void {
        $userAgent = $this->getUserAgent($visitorCode);
        $lines = $this->popUnsentData($visitorCode);
        if (($experimentId !== null) && ($variationId !== null)) {
            array_push($lines, new ExperimentEvent($experimentId, $variationId));
        } elseif (count($lines) === 0) {
            array_push($lines, new ActivityEvent());
        }
        $this->networkManager->sendTrackingData($visitorCode, $lines, $userAgent, $this->debugMode);
    }

    private function calculateVariationForExperiment(string $visitorCode, Experiment $xpConf): ?int
    {
        $hashDouble = $this->obtainHashDouble($visitorCode, $xpConf->id, $xpConf->variationConfigurations);
        $total = 0.0;
        foreach ($xpConf->variationConfigurations as $vid => $variationConfiguration) {
            $total += $variationConfiguration->deviation;
            if ($total >= $hashDouble) {
                return $vid;
            }
        }
        return null;
    }
}

class ConfigurationParser
{
    public static function parse($configurationFilePath)
    {
        $configuration = array();
        try {
            if (file_exists($configurationFilePath) && is_file($configurationFilePath)) {
                $config = file_get_contents($configurationFilePath, true);
                $configuration = json_decode($config, true);
            }
        } catch (Exception $e) {
        }
        return $configuration;
    }
}
