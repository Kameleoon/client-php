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

use Kameleoon\Helpers\URLEncoding;
use Kameleoon\Storage\VariationStorage;
use Kameleoon\Targeting\Conditions\CustomDatum;
use Kameleoon\Targeting\Conditions\ExclusiveExperiment;
use Kameleoon\Targeting\Conditions\TargetedExperiment;
use Kameleoon\Targeting\TargetingEngine;

use Kameleoon\Configuration\Configuration;
use Kameleoon\Configuration\Experiment;
use Kameleoon\Configuration\FeatureFlagV2;
use Kameleoon\Configuration\Rule;
use Kameleoon\Configuration\Variation;
use Kameleoon\Exception\FeatureVariableNotFound;

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'Helpers'.DIRECTORY_SEPARATOR.'Version.php');

class KameleoonClientImpl implements KameleoonClient
{
    const SECONDS_BETWEEN_CONFIGURATION_UPDATE = 3600;
    const API_CONFIGURATION_URL = "https://client-config.kameleoon.com";
    const API_SSX_URL = "https://api-ssx.kameleoon.com";
    const API_DATA_URL = "https://api-data.kameleoon.com";
    const REAL_TIME_CONFIG_URL = "https://events.kameleoon.com:8110";
    const HEXADECIMAL_ALPHABET = "0123456789ABCDEF";
    const NONCE_BYTE_LENGTH = 8;
    const DEFAULT_KAMELEOON_WORK_DIR = "/tmp/kameleoon/php-client/";
    const FILE_CONFIGURATION_NAME = "kameleoon-configuration-";
    const VISITOR_CODE_MAX_LENGTH = 255;

    private $siteCode;
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
    private $realTimeManager = null;
    private $variationStorage;
    private $userAgentData = null;

    public function __construct($siteCode, $configurationFilePath)
    {
        $this->siteCode = $siteCode;
        $this->configurations = (object) array(
            "experiments" => array(),
            "featureFlags" => array()
        );
        $this->unsentData = array();
        $this->targetingData = array();
        $this->configurationLoaded = false;
        $this->variationStorage = new VariationStorage();
        $this->userAgentData = array();

        $this->commonConfiguration = ConfigurationParser::parse($configurationFilePath);
        $this->kameleoonWorkDir = isset($this->commonConfiguration["kameleoon_work_dir"]) ? $this->commonConfiguration["kameleoon_work_dir"] : self::DEFAULT_KAMELEOON_WORK_DIR;
        $this->configurationFilePath = $this->kameleoonWorkDir . self::FILE_CONFIGURATION_NAME . $this->siteCode . ".json";
        $this->refreshInterval = isset($this->commonConfiguration["actions_configuration_refresh_interval"]) ? $this->commonConfiguration["actions_configuration_refresh_interval"] * 60 : self::SECONDS_BETWEEN_CONFIGURATION_UPDATE;
        if (isset($this->commonConfiguration["debug_mode"])) {
            $this->debugMode = $this->commonConfiguration["debug_mode"] == "true";
        }
        $this->cookieOptions = isset($this->commonConfiguration["cookie_options"]) ? $this->commonConfiguration["cookie_options"] : array();

        if (!is_dir($this->kameleoonWorkDir)) {
            mkdir($this->kameleoonWorkDir, 0755, true);
        }
        $this->environment = isset($this->commonConfiguration["environment"]) ? $this->commonConfiguration["environment"] : $this->environment;

        $this->loadConfiguration(self::DEFAULT_TIMEOUT_MILLISECONDS);
    }

    /*
     * API Methods (Public Methods)
     */

    public function triggerExperiment($visitorCode, $experimentID, $timeOut = self::DEFAULT_TIMEOUT_MILLISECONDS)
    {
        $this->validateVisitorCode($visitorCode);
        $this->loadConfiguration($timeOut);
        $variationId = "reference";
        if (array_key_exists($experimentID, $this->configurations->experiments)) {
            $xpConf = $this->configurations->experiments[$experimentID];
            $this->checkSiteCodeEnable($xpConf);
            if ($this->checkTargeting($visitorCode, $experimentID, $xpConf))
            {
                $noneVariation = true;
                $variationId = 0;
                $savedVariation = $this->variationStorage->getSavedVariation($visitorCode, $xpConf);
                if (!is_null($savedVariation)) {
                    $noneVariation = false;
                    $variationId = $savedVariation;
                } else {
                    $calculatedVariationId = $this->calculateVariationForExperiment($visitorCode, $xpConf);
                    if(!is_null($calculatedVariationId)) {
                        $noneVariation = false;
                        $variationId = $calculatedVariationId;
                        $this->variationStorage->saveVariation($visitorCode, $experimentID, $variationId);
                    }
                }
                $data = $this->getAndClearUnsentData($visitorCode);
                $this->writeRequestToFile(
                    $this->getExperimentRegisterURL($visitorCode, $experimentID, $variationId, $noneVariation),
                    $data,
                    $this->getUserAgent($visitorCode)
                );
                if ($noneVariation)
                {
                    throw new NotAllocated("Visitor {$visitorCode} isn't allocated for experiment {$experimentID}");
                }
                return $variationId;
            } else {
                throw new NotTargeted("Visitor {$visitorCode} isn't targeted for experiment {$experimentID}");
            }
        } else {
            throw new ExperimentConfigurationNotFound("Experiment configuration {$experimentID} isn't found");
        }
    }

    /**
     * @deprecated deprecated since version 3.0.0. Please use `isFeatureActive`
     */
    public function activateFeature(
        string $visitorCode,
        string $featureKey,
        int $timeOut = self::DEFAULT_TIMEOUT_MILLISECONDS): bool
	{
        error_log("Deprecated: `activateFeature` is deprecated. Please use `isFeatureActive`instead ");
        return $this->isFeatureActive($visitorCode, $featureKey, $timeOut);
	}

    public function getFeatureVariationKey(
        string $visitorCode,
        string $featureKey,
        int $timeOut = self::DEFAULT_TIMEOUT_MILLISECONDS): string
    {
        [, $variationKey] = $this->_getFeatureVariationKey($visitorCode, $featureKey, $timeOut);
        return $variationKey;
    }

    public function getFeatureVariable(
        string $visitorCode,
        string $featureKey,
        string $variableName,
        int $timeOut = self::DEFAULT_TIMEOUT_MILLISECONDS): array|object|string|float|int|bool|null
    {
        [$featureFlag, $variationKey] = $this->_getFeatureVariationKey($visitorCode, $featureKey, $timeOut);
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

    public function isFeatureActive(
        string $visitorCode,
        string $featureKey,
        int $timeOut = self::DEFAULT_TIMEOUT_MILLISECONDS): bool
    {
        [$ff, $variationKey] = $this->_getFeatureVariationKey($visitorCode, $featureKey, $timeOut);
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
        if (!isset($this->targetingData[$visitorCode]))
        {
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
        if (!is_null($visitorCode)) {
            $this->validateVisitorCode($visitorCode);
            $data = $this->getAndClearUnsentData($visitorCode);
            $this->writeRequestToFile(
                $this->getDataTrackingURL($visitorCode),
                $data,
                $this->getUserAgent($visitorCode)
            );
        } else {
            foreach ($this->getUnsentUsers() as $user) {
                $this->flush($user);
            }
        }
    }

    /**
     * @deprecated deprecated since version 3.0.0. Please use `getVisitorCode`
     */
    public function obtainVisitorCode($topLevelDomain, $visitorCode = NULL)
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
            }
            elseif (isset($this->cookieOptions["domain"])) {
                $domain = $this->cookieOptions["domain"];
            }
            else {
                throw new InvalidArgumentException('Domain is required');
            }

            if (version_compare(phpversion(), '7.3', '<')) {
                setcookie("kameleoonVisitorCode", $visitorCode, time() + 32832000, "/", $domain, $secure, $httpOnly);
            } else {
                $cookie_options = array (
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
    public function obtainVariationAssociatedData($variationId)
    {
        error_log("Deprecated: `obtainVariationAssociatedData` is deprecated. Please use `getVariationAssociatedData`instead ");
        return $this->getVariationAssociatedData($variationId);
    }

    public function getVariationAssociatedData($variationId)
    {
        //check that configuration is loaded
        $this->loadConfiguration(self::DEFAULT_TIMEOUT_MILLISECONDS);

        $result = null;
        foreach ($this->configurations->experiments as $experiment) {
            foreach ($experiment->variationConfigurations as $vid => $variationConfiguration) {
                if ($vid == $variationId || strval($vid) == $variationId) {
                    $result = $variationConfiguration->customJson;
                }
            }
        }
        if ($result == null)
        {
            throw new VariationConfigurationNotFound("Variation configuration not found");
        }
        return $result;
    }

    public function retrieveDataFromRemoteSource($key, $timeout = 2000) {
        $response = $this->performGetServerCall($this->getAPIDataRequestURL($key), $timeout);
        return json_decode($response);
    }

    public function getFeatureAllVariables(string $featureKey, string $variationKey): array {
        $featureFlag = $this->findFeatureFlagV2($featureKey);
        $variation = $featureFlag->getVariation($variationKey);
        if (is_null($variation)) {
            throw new VariationConfigurationNotFound("Variation key {$variationKey} not found");
        }
        return array_map(fn($var) => $var->getValue() , $variation->variables);
    }

    public function getExperimentList(): array {
        return array_keys($this->configurations->experiments);
    }

    public function getExperimentListForVisitor(string $visitorCode, bool $onlyAllocated = true): array {
        $this->validateVisitorCode($visitorCode);
        $arrayIds = array();
        foreach ($this->configurations->experiments as $experiment) {
            if ($this->checkTargeting($visitorCode, $experiment->id, $experiment) &&
                (!$onlyAllocated || !is_null($this->calculateVariationForExperiment($visitorCode, $experiment)))) {
                    array_push($arrayIds, $experiment->id);
            }
        }
        return $arrayIds;
    }

    public function getFeatureList(): array {
        return array_keys($this->configurations->featureFlagsV2);
    }

    public function getActiveFeatureListForVisitor(string $visitorCode): array {
        $this->validateVisitorCode($visitorCode);
        $arrayKeys = array();
        foreach ($this->configurations->featureFlagsV2 as $featureFlag) {
            [$variationKey,] = $this->calculateVariationKeyForFeature($visitorCode, $featureFlag);
            if ($variationKey != Variation::VARIATION_OFF) {
                array_push($arrayKeys, $featureFlag->featureKey);
            }
        }
        return $arrayKeys;
    }

    /*
     * Helper Methods (Private Methods)
     */

    private function _getFeatureVariationKey(
        string $visitorCode,
        string $featureKey,
        int $timeOut = self::DEFAULT_TIMEOUT_MILLISECONDS): array
    {
        $this->validateVisitorCode($visitorCode);
        $this->loadConfiguration($timeOut);
        $featureFlag = $this->findFeatureFlagV2($featureKey);
        [$variationKey, $rule] = $this->calculateVariationKeyForFeature($visitorCode, $featureFlag);
        if (!is_null($rule) && $rule->type == Rule::EXPERIMENTATION) {
            $this->makeTrackingRequest($visitorCode, $variationKey, $rule);
        }
        return [$featureFlag, $variationKey];
    }


    //load configuration if it was not loaded
	private function loadConfiguration(int $timeOut)
    {
        $needUpdateByRefreshInterval = file_exists($this->configurationFilePath) && (time() >= filemtime($this->configurationFilePath) + $this->refreshInterval);
        if (!$this->configurationLoaded || $needUpdateByRefreshInterval)
        {
            if (file_exists($this->configurationFilePath)) {
                $fp = fopen($this->configurationFilePath, "r+");
                if (!$needUpdateByRefreshInterval || !flock($fp, LOCK_EX)) {
                    try {
                        $obj = json_decode(file_get_contents($this->configurationFilePath, true));
                        $this->configurations = Configuration::parse($obj);
                    }
                    catch (Exception $e) {
                        if (!flock($fp, LOCK_EX)) {
                            $this->updateCampaignConfiguration($timeOut);
                            flock($fp, LOCK_UN);
                        }
                    }
                } else {
                    $this->updateCampaignConfiguration($timeOut);
                    flock($fp, LOCK_UN);
                }
            } else {
                $this->updateCampaignConfiguration($timeOut);
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
        if (null != $targetingSegment)
        {
            $targetingTree = $targetingSegment->getTargetingTree();
            // obtaining targeting checking result and assigning targeting to container
            $targeting = TargetingEngine::checkTargetingTree($targetingTree, function($type) use($visitorCode, $containerID) {
               return $this->getConditionData($type, $visitorCode, $containerID);
            });
        }

        return $targeting;
    }

	private function getConditionData($type, $visitorCode, $campaignId)
    {
        switch($type) {
            case CustomDatum::TYPE:
                return $this->getTargetingData($visitorCode);
            case TargetedExperiment::TYPE:
                return $this->variationStorage->getSavedVariations($visitorCode);
            case ExclusiveExperiment::TYPE:
                return [$campaignId, $this->variationStorage->getSavedVariations($visitorCode)];
        }
	}

    private function obtainHashDouble(string $visitorCode, int $containerID, array $variationConfigurations) {
        return $this->_obtainHashDouble($visitorCode, $containerID, $variationConfigurations);
    }

    private function obtainHashDoubleV2(string $visitorCode, int $containerID, string $suffix = '') {
        return $this->_obtainHashDouble($visitorCode, $containerID, array(), $suffix);
    }

    private function _obtainHashDouble(
        string $visitorCode,
        int $containerID,
        array $variationConfigurations,
        string $suffix = '')
    {
        $respoolTimes = array();
        foreach ($variationConfigurations as $v) {
            if ($v->respoolTime != null) {
                array_push($respoolTimes, $v->respoolTime);
            }
        }
        return floatval(intval(substr(hash("sha256", $visitorCode . $containerID . $suffix . join("", $respoolTimes)), 0, 8), 16) / pow(2, 32));
    }

    private function getCommonSSXParameters($visitorCode)
    {
        return "nonce=" . self::obtainNonce() . "&siteCode=" . $this->siteCode . "&visitorCode=" . $visitorCode;
    }

    private function getDataTrackingURL($visitorCode)
    {
        return static::API_SSX_URL . "/dataTracking?" . $this->getCommonSSXParameters($visitorCode);
    }

    private function getAPIDataRequestURL($key)
    {
        return static::API_DATA_URL . "/data?siteCode=" . $this->siteCode . "&key=" . URLEncoding::encodeURIComponent($key);
    }

    private function getRealTimeConfigurationURL($siteCode) {
        return static::REAL_TIME_CONFIG_URL . '/sse?siteCode=' . $siteCode;
    }

    private function getExperimentRegisterURL($visitorCode, $experimentID, $variationId = null, $noneVariation = null)
    {
        $url = static::API_SSX_URL . "/experimentTracking?" . $this->getCommonSSXParameters($visitorCode) . "&experimentId=" . $experimentID . "";
        if (!is_null($variationId)) {
            $url .= "&variationId=" . $variationId;
        }
        if ($this->debugMode) {
            $debugParameters = "";
            try {
                $debugParameters .= "&debug=true";

                // Add current url
                $currentUrl = "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
                $debugParameters .= "&url=" . rawurlencode($currentUrl);

                // Add visitor IP
                if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                    $ip = $_SERVER['HTTP_CLIENT_IP'];
                } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                } else {
                    $ip = $_SERVER['REMOTE_ADDR'];
                }
                $debugParameters .= "&ip=" . rawurlencode($ip);

                $debugParameters .= "&ua=" . rawurlencode($_SERVER['HTTP_USER_AGENT']);
            } catch (Exception $e) {}
            $url .= $debugParameters;
        }
        return $url . ($noneVariation ? "&noneVariation=true" : "");
    }

    public static function obtainNonce()
    {
        $alphabetLength = strlen(self::HEXADECIMAL_ALPHABET);
        $result = "";

        for ($i = 0; $i < self::NONCE_BYTE_LENGTH * 2; $i++) {
            $randomNumber = floor((mt_rand() / mt_getrandmax()) * $alphabetLength);
            $result .= substr(self::HEXADECIMAL_ALPHABET, $randomNumber, 1);
        }

        return $result;
    }

    private function updateCampaignConfiguration($timeOut)
    {
        try
        {
            $siteCodeURL = "/mobile?siteCode=" . $this->siteCode;
            $environmentURL = $this->environment != null ? "&environment=" . $this->environment : "";
            $configurationRequest = curl_init(self::API_CONFIGURATION_URL . $siteCodeURL . $environmentURL);
            curl_setopt($configurationRequest, CURLOPT_TIMEOUT_MS, $timeOut);
            curl_setopt($configurationRequest, CURLOPT_CONNECTTIMEOUT_MS, $timeOut);
            curl_setopt($configurationRequest, CURLOPT_RETURNTRANSFER, 1);
            $configurationOutput = curl_exec($configurationRequest);
            curl_close($configurationRequest);
            $configuration = json_decode($configurationOutput);

            if (isset($configuration->experiments) &&
                isset($configuration->featureFlags) &&
                isset($configuration->featureFlagConfigurations))
            {
                file_put_contents($this->configurationFilePath, $configurationOutput);
                $this->configurations = Configuration::parse($configuration);
            }
            else
            {
                $configuration = json_decode(file_get_contents($this->configurationFilePath, true));
                $this->configurations = Configuration::parse($configuration);
                $this->updateConfigurationFileModificationTime();
            }
        } catch (Exception $e) {
            echo "Update configuration file failed: " . $e->getMessage();
        } finally {
            $this->updateConfigurationFileModificationTime();
        }
    }

    private function updateConfigurationFileModificationTime() {
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

    private function getTargetingData($visitorCode)
    {
        if (!isset($this->targetingData[$visitorCode]))
        {
            $this->targetingData[$visitorCode] = array();
        }
        return $this->targetingData[$visitorCode];
    }

    private function emptyUnsentData($visitorCode)
    {
        unset($this->unsentData[$visitorCode]);
    }

    private function performGetServerCall($url, $timeOut = self::DEFAULT_TIMEOUT_MILLISECONDS)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, $timeOut);
        $response = curl_exec($curl);
        if (curl_error($curl)) {
            throw new Exception(curl_error($curl));
        }
        curl_close($curl);
        return $response;
    }

    private function getRequestsFileName()
    {
        return "requests-" . floor(time() / 60) . ".sh";
    }

    private function writeRequestToFile($url, $data, $userAgent)
    {
        $headerUserAgent = " ";
        if (!is_null($userAgent)) {
            $headerUserAgent = " -H \"User-Agent: {$userAgent->getValue()}\" ";
        }
        $version_sdk = VERSION_SDK;
        $requestText = "curl -s -S --tlsv1.2 --tls-max 1.2 -X POST -H \"Kameleoon-Client: sdk/php/$version_sdk\"{$headerUserAgent}\"$url\"";
        if ($data != null) {
            $requestText .= " -d '" . $data . "'";
        }
        $requestText .= " & r=\${r:=0};((r=r+1));if [ \$r -eq 64 ];then r=0;wait;fi;" . PHP_EOL;
        file_put_contents($this->kameleoonWorkDir . $this->getRequestsFileName(), $requestText, FILE_APPEND | LOCK_EX);
    }

    private function validateVisitorCode($visitorCode) {
        if (isset($visitorCode) == false || strlen($visitorCode) == 0) {
            throw new VisitorCodeNotValid("Visitor code is empty");
        } else if (strlen($visitorCode) > self::VISITOR_CODE_MAX_LENGTH) {
            throw new VisitorCodeNotValid("Visitor max length is " . self::VISITOR_CODE_MAX_LENGTH . " characters");
        } else {
            return $visitorCode;
        }
    }

    private function checkSiteCodeEnable($expOrFF) {
        if (!$expOrFF->isSiteCodeEnabled) {
            throw new SiteCodeDisabled("Sitecode '" . $this->siteCode . "' disabled");
        }
    }

    private function checkFeatureIdIsString($featureId) {
        if (gettype($featureId) != "string") {
            error_log("Please use `featureId` with type of `string`. This is necessary to support multi-environment feature. Supporting of `int` type will be removed in next releases");
        }
    }

    private function getAndClearUnsentData($visitorCode) {
        $data = "";
        foreach ($this->getUnsentData($visitorCode) as $d) {
            $data .= $d->obtainFullPostTextLine() . "\n";
        }
        $this->emptyUnsentData($visitorCode);
        return $data;
    }

    private function addUserAgent(string $visitorCode, UserAgent $userAgent) {
        $this->userAgentData[$visitorCode] = $userAgent;
    }

    private function getUserAgent(string $visitorCode): ?UserAgent {
        if (isset($this->userAgentData[$visitorCode])) {
            return $this->userAgentData[$visitorCode];
        } else {
            return NULL;
        }
    }

    private function findFeatureFlagV2(string $featureKey): FeatureFlagV2
    {
        if (isset($this->configurations->featureFlagsV2[$featureKey])) {
            return $this->configurations->featureFlagsV2[$featureKey];
        } else {
            throw new FeatureConfigurationNotFound("Feature {$featureKey} not found");
        }
    }

    private function calculateVariationKeyForFeature(string $visitorCode, FeatureFlagV2 $featureFlag): array {
        // no rules -> return defaultVariationKey
        if (count($featureFlag->rules) > 0) {
            // uses for rule exposition
            $hashRule = $this->obtainHashDoubleV2($visitorCode, $featureFlag->id);
            // uses for variation's expositions
            $hashVariation = $this->obtainHashDoubleV2($visitorCode, $featureFlag->id, 'variation');
            foreach ($featureFlag->rules as $rule) {
                // check if visitor is targeted for rule, else next rule
                if ($this->checkTargeting($visitorCode, $featureFlag->id, $rule)) {
                    // check main expostion for rule with hashRule
                    if ($hashRule < $rule->exposition) {
                        // get variation key with new hashVariation
                        $variationKey = $rule->getVariationKey($hashVariation);
                        // variationKey can be nil for experiment rules only, for targeted rule will be always exist
                        if (!is_null($variationKey)) {
                            return [$variationKey, $rule];
                        }
                    } elseif ($rule->type == Rule::TARGETED_DELIVERY) {
                        // if visitor is targeted but not bucketed for targeted rule then break cycle -> return default
                        break;
                    }
                }
            }
        }
        return [$featureFlag->defaultVariationKey, null];
    }

    private function makeTrackingRequest(
        string $visitorCode,
        string $variationKey,
        Rule $rule)
    {
        if (isset($rule->experimentId)) {
            $data = $this->getAndClearUnsentData($visitorCode);
            $this->writeRequestToFile(
                $this->getExperimentRegisterURL(
                    $visitorCode,
                    $rule->experimentId,
                    $rule->getVariationIdByKey($variationKey)
                ),
                $data,
                $this->getUserAgent($visitorCode)
            );
        } else {
            echo "An attempt to send a request with null experimentId was blocked";
        }
    }

    private function calculateVariationForExperiment(string $visitorCode, Experiment $xpConf): ?int {
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

class Schedule
{
    public $dateStart;
    public $dateEnd;

    public function __construct($schedule)
    {
        $this->dateStart = $this->fetchDate($schedule, "dateStart");
        $this->dateEnd = $this->fetchDate($schedule, "dateEnd");
    }

    private function fetchDate($schedule, $key) {
        return isset($schedule->$key) ? strtotime($schedule->$key) : null;
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
        } catch (Exception $e) {}
        return $configuration;
    }
}
