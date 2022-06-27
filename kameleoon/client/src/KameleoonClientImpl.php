<?php
namespace Kameleoon;

use Exception;
use Kameleoon\Exception\CredentialsNotFound;
use Kameleoon\Exception\ExperimentConfigurationNotFound;
use Kameleoon\Exception\FeatureConfigurationNotFound;
use Kameleoon\Exception\NotActivated;
use Kameleoon\Exception\NotTargeted;
use Kameleoon\Exception\VariationConfigurationNotFound;
use Kameleoon\Exception\InvalidArgumentException;
use Kameleoon\Exception\SiteCodeDisabled;
use Kameleoon\Exception\VisitorCodeNotValid;
use Kameleoon\Targeting\TargetingData;
use Kameleoon\Targeting\TargetingSegment;
use Kameleoon\Targeting\TargetingTreeBuilder;

use Kameleoon\Helpers\URLEncoding;
use Kameleoon\RealTime\RealTimeConfigurationService;
use Kameleoon\Storage\VariationStorage;
use Kameleoon\Targeting\Conditions\CustomDatum;
use Kameleoon\Targeting\Conditions\ExclusiveExperiment;
use Kameleoon\Targeting\Conditions\TargetedExperiment;
use Kameleoon\Targeting\TargetingEngine;

require_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'Helpers'.DIRECTORY_SEPARATOR.'Version.php');

class KameleoonClientImpl implements KameleoonClient
{
    const SECONDS_BETWEEN_CONFIGURATION_UPDATE = 3600;
    const DEFAULT_TIMEOUT_MILLISECONDS = 2000;
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
    private $blockingClient;
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
    private $onUpdateConfigurationHandler = null;
    private $variationStorage;

    public function __construct($siteCode, $blocking, $configurationFilePath)
    {
        $this->siteCode = $siteCode;
        $this->blockingClient = $blocking;
        $this->configurations = (object) array(
            "experiments" => array(),
            "featureFlags" => array()
        );
        $this->unsentData = array();
        $this->targetingData = array();
        $this->configurationLoaded = false;
        $this->variationStorage = new VariationStorage();

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

        $this->loadConfiguration(SELF::DEFAULT_TIMEOUT_MILLISECONDS);
    }

    public function __destruct() {
        if ($this->realTimeManager !== null) {
            $this->realTimeManager->unsubscribe();
        }
    }

    public function trackConversion($visitorCode, int $goalID, $revenue = 0.0)
    {
        $this->addData($this->validateVisitorCode($visitorCode), new Data\Conversion($goalID));
        $this->flush($visitorCode);
    }

    public function triggerExperiment($visitorCode, $experimentID, $timeOut = self::DEFAULT_TIMEOUT_MILLISECONDS)
    {
        $this->validateVisitorCode($visitorCode);
        if ($this->blockingClient) {
            return $this->performPostServerCall($this->getExperimentRegisterURL($visitorCode, $experimentID), $timeOut);
        } else {
            $this->loadConfiguration($timeOut);
            $variationId = "reference";
            if (array_key_exists($experimentID, $this->configurations->experiments)) {
                $xpConf = $this->configurations->experiments[$experimentID];
                $this->checkSiteCodeEnable($xpConf);
                if ($this->checkTargeting($visitorCode, $experimentID, false))
                {
                    $savedVariation = $this->variationStorage->getSavedVariation($visitorCode, $xpConf);
                    if ($savedVariation !== null) {
                        return $savedVariation;
                    } else {
                        $noneVariation = true;
                        $hashDouble = $this->obtainHashDouble($experimentID, $visitorCode, $xpConf->variationConfigurations);
                        $total = 0.0;
    
                        foreach ($xpConf->variationConfigurations as $vid => $variationConfiguration) {
                            $total += $variationConfiguration->deviation;
                            if ($total >= $hashDouble) {
                                $noneVariation = false;
                                $variationId = $vid;
                                break;
                            }
                        }
                        $data = $this->getAndClearUnsentData($visitorCode);
                        $this->writeRequestToFile($this->getExperimentRegisterURL($visitorCode, $experimentID, $variationId, $noneVariation), $data);
    
                        if ($noneVariation)
                        {
                            throw new NotActivated("Experiment not activated");
                        }
                        $this->variationStorage->saveVariation($visitorCode, $experimentID, $variationId);
                        return $variationId;
                    }
                } else {
                    throw new NotTargeted("Experiment not targeted");
                }
            } else {
                throw new ExperimentConfigurationNotFound('Experiment configuration not found');
            }
        }
    }

    public function activateFeature($visitorCode, $featureId, $timeOut = 2000)
	{
        $this->checkFeatureIdIsString($featureId);
        $this->validateVisitorCode($visitorCode);
        if(isset($this->configurations->featureFlags[strval($featureId)]) == false) {
            $this->loadConfiguration($timeOut);
            $arrayFF = array_filter($this->configurations->featureFlags, function($v, $k) use ($featureId) { return $v->identificationKey == $featureId; }, ARRAY_FILTER_USE_BOTH);
            $featureId = count($arrayFF) > 0 ? array_key_first($arrayFF) : $featureId;
        }
        if ($this->blockingClient)
        {
            return $this->performPostServerCall($this->getExperimentRegisterURL($visitorCode, $featureId), $timeOut) == "null" ? false : true;
        }
        else
        {
            $this->loadConfiguration($timeOut);
            $result = false;
            if (array_key_exists($featureId, $this->configurations->featureFlags))
            {
                $ffConf = $this->configurations->featureFlags[strval($featureId)];
                $this->checkSiteCodeEnable($ffConf);
                if($this->checkTargeting($visitorCode, $featureId, true))
                {
                    if($ffConf->isScheduleActive(time())) {
                        $hashDouble = $this->obtainHashDouble($featureId, $visitorCode, $ffConf->variationConfigurations);
                        $variationId = 0;
                        $total = 0.0;
                        foreach ($ffConf->variationConfigurations as $vid => $variationConfiguration) {
                            $total += $variationConfiguration->deviation;
                            if ($total >= $hashDouble) {
                                $result = $vid != 0;
                                $variationId = $vid;
                                break;
                            }
                        }
                        $data = $this->getAndClearUnsentData($visitorCode);
                        $this->writeRequestToFile($this->getExperimentRegisterURL($visitorCode, $featureId, $variationId, false), $data);
                    } else {
                        return $result;
                    }
                } else {
                    throw new NotTargeted("Feature not targeted");
                }
            }
            else
            {
                throw new FeatureConfigurationNotFound("Feature configuration not found");
            }
            return $result;
        }
	}

    //load configuration if it was not loaded
	private function loadConfiguration($timeOut, $timeStamp = 0)
    {
        $needUpdateByRefreshInterval = file_exists($this->configurationFilePath) && (time() >= filemtime($this->configurationFilePath) + $this->refreshInterval);
        $needUpdateByRefreshEvent = $timeStamp != 0;
        if (!$this->configurationLoaded || $needUpdateByRefreshInterval || $needUpdateByRefreshEvent)
        {
            if (file_exists($this->configurationFilePath) && !$needUpdateByRefreshEvent) {
                $fp = fopen($this->configurationFilePath, "r+");
                if (!$needUpdateByRefreshInterval || !flock($fp, LOCK_EX)) {
                    try {
                        $obj = json_decode(file_get_contents($this->configurationFilePath, true));
                        $this->configurations = Configurations::parse($obj);
                    }
                    catch (Exception $e) {
                        if (!flock($fp, LOCK_EX)) {
                            $this->updateCampaignConfiguration($timeOut, $timeStamp);
                            flock($fp, LOCK_UN);
                        }
                    }
                } else {
                    $this->updateCampaignConfiguration($timeOut, $timeStamp);
                    flock($fp, LOCK_UN);
                }
            } else {
                $this->updateCampaignConfiguration($timeOut, $timeStamp);
            }
            $realTimeUpdate = $this->configurations->settings->getRealTimeUpdate();
            $this->manageConfigurationUpdate($realTimeUpdate);
        }
        $this->configurationLoaded = true;
    }

    // check targeting
    private function checkTargeting($visitorCode, $containerID, $isFF)
    {
		$targeting = true;

		// performing targeting
		$xpConf = null;
		if ($isFF)
        {
            if (isset($this->configurations->featureFlags[strval($containerID)]))
            {
                $xpConf = $this->configurations->featureFlags[strval($containerID)];
            }
        }
		else
        {
            if (isset($this->configurations->experiments[strval($containerID)]))
            {
                $xpConf = $this->configurations->experiments[strval($containerID)];
            }
        }
		if ($xpConf != null)
		{
			if(true == $xpConf->forceTargeting)
			{
				// assigning targeting to container
				$targeting = true;
			}
            else
            {
                $targetingTree = null;

                if (null != $xpConf->targetingSegment)
                {
                    $targetingTree = $xpConf->targetingSegment->getTargetingTree();
                }
                // obtaining targeting checking result and assigning targeting to container
                $targeting = TargetingEngine::checkTargetingTree($targetingTree, function($type) use($visitorCode, $containerID) {
                    return $this->getConditionData($type, $visitorCode, $containerID);
                });
            }
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

    private function obtainHashDouble($containerID, $visitorCode, $variationConfigurations)
    {
        $respoolTimes = array();
        foreach ($variationConfigurations as $v) {
            if ($v->respoolTime != null) {
                array_push($respoolTimes, $v->respoolTime);
            }
        }
        return floatval(intval(substr(hash("sha256", $visitorCode . $containerID . join("", $respoolTimes)), 0, 8), 16) / pow(2, 32));
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

    private function updateCampaignConfiguration($timeOut, $timeStamp)
    {
        try
        {
            $siteCodeURL = "/mobile?siteCode=" . $this->siteCode;
            $environmentURL = $this->environment != null ? "&environment=" . $this->environment : "";
            $timeStampURL = $timeStamp != 0 ? "&ts=" . strval($timeStamp) : "";
            $configurationRequest = curl_init(self::API_CONFIGURATION_URL . $siteCodeURL . $environmentURL . $timeStampURL);
            curl_setopt($configurationRequest,CURLOPT_TIMEOUT, $timeOut);
            curl_setopt($configurationRequest, CURLOPT_RETURNTRANSFER, 1);
            $configurationOutput = curl_exec($configurationRequest);
            curl_close($configurationRequest);
            $configuration = json_decode($configurationOutput);

            if (isset($configuration->experiments) && isset($configuration->featureFlags))
            {
                file_put_contents($this->configurationFilePath, $configurationOutput);
                $this->configurations = Configurations::parse($configuration);
            }
            else
            {
                $configuration = json_decode(file_get_contents($this->configurationFilePath, true));
                $this->configurations = Configurations::parse($configuration);
            }        
        } catch (Exception $e) {
            
        }
    }

    private function manageConfigurationUpdate($realTimeUpdate) {
        if($this->realTimeManager === null && $realTimeUpdate) {
            $this->realTimeManager = new RealTimeConfigurationService();
            $this->realTimeManager->subscribe($this->getRealTimeConfigurationURL($this->siteCode), function ($data) {
                $this->loadConfiguration(self::DEFAULT_TIMEOUT_MILLISECONDS, $data->ts);
                call_user_func($this->onUpdateConfigurationHandler);
                print_r($data);
            });
        } else if ($this->realTimeManager !== null && !$realTimeUpdate) {
            $this->realTimeManager->unsubscribe();
            $this->realTimeManager = null;
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

    public function addData($visitorCode, ...$data)
    {
        $this->validateVisitorCode($visitorCode);
        $this->getUnsentData($visitorCode);
        if (!isset($this->targetingData[$visitorCode]))
        {
            $this->targetingData[$visitorCode] = array();
        }
        foreach ($data as $datum) {
            array_push($this->unsentData[$visitorCode], $datum);
            array_push($this->targetingData[$visitorCode], new TargetingData($datum));
        }
    }

    public function flush($visitorCode = null)
    {
        if (!is_null($visitorCode)) {
            $this->validateVisitorCode($visitorCode);
            $data = $this->getAndClearUnsentData($visitorCode);
            $this->writeRequestToFile($this->getDataTrackingURL($visitorCode), $data);
        } else {
            foreach ($this->getUnsentUsers() as $user) {
                $this->flush($user);
            }
        }
    }

    public function performPostServerCall($url, $timeOut, $data = null)
    {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT_MS, $timeOut);
        if (!is_null($data)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        $response = curl_exec($curl);
        if (curl_error($curl)) {
            throw new Exception('API-SSX call returned status code 404');
        }
        curl_close($curl);
        return $response;
    }

    public function performGetServerCall($url, $timeOut = self::DEFAULT_TIMEOUT_MILLISECONDS)
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

    private function writeRequestToFile($url, $data)
    {
        $requestText = "curl -s -S --tlsv1.2 --tls-max 1.2 -X POST -H \"Kameleoon-Client: sdk/php/" . VERSION_SDK . "\" \"" . $url . "\"";
        if ($data != null) {
            $requestText .= " -d '" . $data . "'";
        }
        $requestText .= " & r=\${r:=0};((r=r+1));if [ \$r -eq 64 ];then r=0;wait;fi;" . PHP_EOL;
        file_put_contents($this->kameleoonWorkDir . $this->getRequestsFileName(), $requestText, FILE_APPEND | LOCK_EX);
    }

    // Here you must provide your own base domain, eg mydomain.com
    public function obtainVisitorCode($topLevelDomain = null, $visitorCode = null)
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

    public function obtainVariationAssociatedData($variationId)
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

    public function obtainFeatureVariable($featureIdOrName, $variableName)
    {
        $this->checkFeatureIdIsString($featureIdOrName);
        
        $BOOLEAN_TYPE = "Boolean"; $STRING_TYPE = "String"; $NUMBER_TYPE = "Number"; $JSON_TYPE = "JSON";

        //check that configuration is loaded
        $this->loadConfiguration(self::DEFAULT_TIMEOUT_MILLISECONDS);

        $result = null;
        foreach ($this->configurations->featureFlags as $id => $ff) {
            foreach ($ff->variationConfigurations as $vid => $variationConfiguration) {
                if ($featureIdOrName == $ff->identificationKey || $id == $featureIdOrName || strval($id) == $featureIdOrName) {
                    try {
                        if ($vid != 0)
                        {
                            $type = $variationConfiguration->customJson->{$variableName}->type;
                            $result = $variationConfiguration->customJson->{$variableName}->value;
                            switch ($type) {
                                case $BOOLEAN_TYPE:
                                    $result = filter_var($result, FILTER_VALIDATE_BOOLEAN);
                                    break;
                                case $NUMBER_TYPE:
                                    $result = filter_var($result, FILTER_VALIDATE_INT);
                                    break;
                                case $JSON_TYPE:
                                    $result = json_decode($result);
                                    break;
                            }
                        }
                    }
                    catch (Exception $e){}
                }
            }
        }
        if ($result === null)
        {
            throw new FeatureConfigurationNotFound("Feature configuration not found");
        }
        return $result;
    }

    public function retrieveDataFromRemoteSource($key, $timeout = 2000) {
        $response = $this->performGetServerCall($this->getAPIDataRequestURL($key), $timeout);
        return json_decode($response);
    }

    public function onUpdateConfiguration(callable $onUpdate) {
        $this->onUpdateConfigurationHandler = $onUpdate;
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
            error_log(print_r("Please use `featureId` with type of `string`. This is necessary to support multi-environment feature. Supporting of `int` type will be removed in next releases", TRUE)); 
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
}

class VariationConfiguration
{
    public $deviation;
    public $respoolTime;
    public $customJson;

    public function __construct($deviation, $respoolTime, $customJson)
    {
        $this->deviation = $deviation;
        $this->respoolTime = $respoolTime;
        try {
            $this->customJson = json_decode($customJson);
        } catch (Exception $e) {
            $this->customJson = null;
        }
    }
}

class Experiment
{
    public $id;
    public $variationConfigurations;
    public $forceTargeting;
    public $targetingSegment;
    public $isSiteCodeEnabled;

    public function __construct($experiment)
    {
        $this->id = $experiment->id;
        $this->variationConfigurations = array();
        $this->forceTargeting = isset($experiment->forceTargeting) ? $experiment->forceTargeting : false;
        $this->targetingSegment = null;

        if ($experiment->segment != null)
        {
            $this->targetingSegment = new TargetingSegment();
            $targetingTreeBuilder = new TargetingTreeBuilder();
            $targetingTree = $targetingTreeBuilder->createTargetingTree($experiment->segment->conditionsData);
            $this->targetingSegment->setTargetingTree($targetingTree);
        }

        $sortedDeviations = $experiment->deviations;
        usort($sortedDeviations, function (object $first, object $second) { return intval($first->variationId) <=> intval($second->variationId); });
        foreach ($sortedDeviations as $deviation) {
            $variationId = $deviation->variationId == "origin" ? 0 : intval($deviation->variationId);
            $deviation = floatval($deviation->value);
            $respoolTime = null;
            if (isset($experiment->respoolTime)) {
                foreach ($experiment->respoolTime as $rt) {
                    if ($rt->variationId == $variationId || ($variationId == 0 && $rt->variationId == "origin")) {
                        $respoolTime = floatval($rt->value);
                        break;
                    }
                }
            }
            $customJson = '{}';
            foreach ($experiment->variations as $variation) {
                if ($variation->id == $variationId || ($variationId == 0 && $variation->id == "origin")) {
                    $customJson = $variation->customJson;
                    break;
                }
            }
            $this->variationConfigurations[$variationId] = new VariationConfiguration($deviation, $respoolTime, $customJson);
        }

        $this->isSiteCodeEnabled = $experiment->siteEnabled;
    }
}

class Schedule
{
    public $dateStart;
    public $dateEnd;

    public function __construct($schedule) {
        $this->dateStart = $this->fetchDate($schedule, "dateStart");
        $this->dateEnd = $this->fetchDate($schedule, "dateEnd");
    }
    
    private function fetchDate($schedule, $key) {
        return isset($schedule->$key) ? strtotime($schedule->$key) : null;
    }
}

class FeatureFlag
{
    private const STATUS_ACTIVE = "ACTIVE";
    private const FEATURE_STATUS_DEACTIVATED = "DEACTIVATED";
    public $variationConfigurations;
    public $forceTargeting;
    public $targetingSegment;
    public $identificationKey;
    public $isSiteCodeEnabled;
    private $featureStatus;
    private $status;
    private $schedules;

    public function __construct($ff)
    {     
        $this->variationConfigurations = array();
        $this->identificationKey = isset($ff->identificationKey) ? $ff->identificationKey : null;
        $this->forceTargeting = isset($ff->forceTargeting) ? $ff->forceTargeting : false;

        $this->targetingSegment = null;

        if ($ff->segment != null)
        {
            $this->targetingSegment = new TargetingSegment();
            $targetingTreeBuilder = new TargetingTreeBuilder();
            $targetingTree = $targetingTreeBuilder->createTargetingTree($ff->segment->conditionsData);
            $this->targetingSegment->setTargetingTree($targetingTree);
        }
        
        $deviations = array();
        array_push($deviations, 
            ["variationId" => 0, "value" => floatval(1 - $ff->expositionRate)],
            ["variationId" => $ff->variations[0]->id, "value" => floatval($ff->expositionRate)],
        );

        foreach ($deviations as $deviation) {
            $variationId = $deviation["variationId"] == "origin" ? 0 : intval($deviation["variationId"]);
            $deviationValue = floatval($deviation["value"]);
            $respoolTime = null;
            if (isset($ff->respoolTime)) {
                foreach ($ff->respoolTime as $rt) {
                    if ($rt->variationId == $variationId || ($variationId == 0 && $rt->variationId == "origin")) {
                        $respoolTime = floatval($rt->value);
                    }
                }
            }
            $customJson = '{}';
            foreach ($ff->variations as $variation) {
                if ($variation->id == $variationId || ($variationId == 0 && $variation->id == "origin")) {
                    $customJson = $variation->customJson;
                }
            }
            $this->variationConfigurations[$variationId] = new VariationConfiguration($deviationValue, $respoolTime, $customJson);
        }

        // Temporary fix for ff
        foreach ($ff->variations as $variation) {
            if (!isset($this->variationConfigurations[$variation->id]))
            {
                $deviation = 1 - $this->variationConfigurations["0"]->deviation;
                $respoolTime = null;
                if (isset($ff->respoolTime)) {
                    if (isset($ff->respoolTime[$variation->id])) {
                        $respoolTime = floatval($ff->respoolTime[$variation->id]);
                    }
                }
                $customJson = $variation->customJson;
                $this->variationConfigurations[$variation->id] = new VariationConfiguration($deviation, $respoolTime, $customJson);
            }
        }

        $this->status = $ff->status;
        $this->featureStatus = $ff->featureStatus;

        $this->schedules = array();
        if(isset($ff->schedules)) {
            foreach($ff->schedules as $schedule) {
                array_push($this->schedules, new Schedule($schedule));
            }
        }

        $this->isSiteCodeEnabled = $ff->siteEnabled;
    }

    public function isScheduleActive($currentTimestamp) {
        // if no schedules then need to fetch current status
        $currentStatus = $this->status == $this::STATUS_ACTIVE;
        if($this->featureStatus == $this::FEATURE_STATUS_DEACTIVATED || empty($this->schedules)) {
            return $currentStatus;
        }
        // need to find if date is in any period -> active or not -> not activate
        foreach($this->schedules as $schedule) {
            if (($schedule->dateStart == null || $schedule->dateStart < $currentTimestamp) &&
                ($schedule->dateEnd == null || $schedule->dateEnd > $currentTimestamp) ) {
                    return true;
            }
        }
        return false;
    }
}

class Configurations
{
    public static function parse($json)
    {
        $configurations = (object) array();
        $configurations->experiments = array();
        $configurations->featureFlags = array();

        try {
            foreach ($json->experiments as $experiment) {
                $configurations->experiments[$experiment->id] = new Experiment($experiment);
            }
        } catch (Exception $e) {
        }
        try {
            foreach ($json->featureFlags as $ff) {
                $configurations->featureFlags[$ff->id] = new FeatureFlag($ff);
            }
        } catch (Exception $e) {
        }

        $configurations->settings = new Settings($json->configuration);

        return $configurations;
    }
}

class Settings
{
    private $realTimeUpdate = false;

    public function __construct($settings) {
        if ($settings != null) {
            $this->realTimeUpdate = $settings->realTimeUpdate;
        }
    }

    public function getRealTimeUpdate() {
        return $this->realTimeUpdate;
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
