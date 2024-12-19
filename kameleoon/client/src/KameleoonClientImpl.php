<?php

namespace Kameleoon;

use Exception;
use Generator;
use Kameleoon\Configuration\DataFile;
use Kameleoon\Configuration\FeatureFlag;
use Kameleoon\Configuration\Rule;
use Kameleoon\Configuration\Variation;
use Kameleoon\Configuration\VariationByExposition;
use Kameleoon\Data\CustomData;
use Kameleoon\Data\Manager\AssignedVariation;
use Kameleoon\Data\Manager\ForcedExperimentVariation;
use Kameleoon\Data\Manager\Visitor;
use Kameleoon\Data\Manager\VisitorManager;
use Kameleoon\Data\Manager\VisitorManagerImpl;
use Kameleoon\Exception\DataFileInvalid;
use Kameleoon\Exception\FeatureEnvironmentDisabled;
use Kameleoon\Exception\FeatureExperimentNotFound;
use Kameleoon\Exception\FeatureVariableNotFound;
use Kameleoon\Exception\FeatureVariationNotFound;
use Kameleoon\Exception\SiteCodeIsEmpty;
use Kameleoon\Helpers\HashDouble;
use Kameleoon\Helpers\VisitorCodeManager;
use Kameleoon\Logging\KameleoonLogger;
use Kameleoon\Managers\Data\DataManager;
use Kameleoon\Managers\Data\DataManagerImpl;
use Kameleoon\Managers\Hybrid\HybridManager;
use Kameleoon\Managers\Hybrid\HybridManagerImpl;
use Kameleoon\Managers\RemoteData\RemoteDataManager;
use Kameleoon\Managers\RemoteData\RemoteDataManagerImpl;
use Kameleoon\Managers\Tracking\TrackingManager;
use Kameleoon\Managers\Tracking\TrackingManagerImpl;
use Kameleoon\Managers\Warehouse\WarehouseManager;
use Kameleoon\Managers\Warehouse\WarehouseManagerImpl;
use Kameleoon\Network\AccessToken\AccessTokenSourceFactoryImpl;
use Kameleoon\Network\AccessTokenSourceImpl;
use Kameleoon\Network\ActivityEvent;
use Kameleoon\Network\Cookie\CookieManager;
use Kameleoon\Network\Cookie\CookieManagerImpl;
use Kameleoon\Network\NetworkManager;
use Kameleoon\Network\NetworkManagerFactory;
use Kameleoon\Network\NetworkManagerFactoryImpl;
use Kameleoon\Targeting\TargetingManager;
use Kameleoon\Targeting\TargetingManagerImpl;
use Kameleoon\Types\Variable;
use Kameleoon\Types\RemoteVisitorDataFilter;

class KameleoonClientImpl implements KameleoonClient
{
    const FILE_CONFIGURATION_NAME = "kameleoon-configuration-";
    const VISITOR_CODE_MAX_LENGTH = 255;

    private DataManager $dataManager;
    private KameleoonClientConfig $clientConfig;
    private string $configurationFilePath;
    private string $visitorCode;

    private VisitorManager $visitorManager;
    private NetworkManager $networkManager;
    private CookieManager $cookieManager;
    private HybridManager $hybridManager;
    private WarehouseManager $warehouseManager;
    private TargetingManager $targetingManager;
    private RemoteDataManager $remoteDataManager;
    private TrackingManager $trackingManager;

    public function __construct(
        string $siteCode,
        KameleoonClientConfig $clientConfig,
        ?NetworkManagerFactory $networkManagerFactory = null)
    {
        KameleoonLogger::debug(
            "CALL: new KameleoonClientImpl(siteCode: '%s', clientConfig: %s, " .
            "networkManagerFactory)", $siteCode, $clientConfig,
        );
        if (empty($siteCode)) {
            throw new SiteCodeIsEmpty("Provided siteCode is empty");
        }

        $this->dataManager = new DataManagerImpl();
        $this->visitorManager = new VisitorManagerImpl($this->dataManager);
        $this->cookieManager = new CookieManagerImpl(
            $this->dataManager, $this->visitorManager, $clientConfig->getCookieOptions()
        );
        $this->targetingManager = new TargetingManagerImpl($this->dataManager, $this->visitorManager);

        $this->clientConfig = $clientConfig;

        $kameleoonWorkDir = $this->clientConfig->getKameleoonWorkDir();
        $this->configurationFilePath = $kameleoonWorkDir . self::FILE_CONFIGURATION_NAME . $siteCode . ".json";

        if (!is_dir($kameleoonWorkDir)) {
            mkdir($kameleoonWorkDir, 0755, true);
        }

        $networkManagerFactory = $networkManagerFactory ?? new NetworkManagerFactoryImpl();
        $this->networkManager = $networkManagerFactory->create(
            $siteCode,
            $this->clientConfig->getEnvironment(),
            $this->clientConfig->getDefaultTimeoutMillisecond(),
            $kameleoonWorkDir,
            new AccessTokenSourceFactoryImpl(
                $this->clientConfig->getClientId(),
                $this->clientConfig->getClientSecret(),
                $kameleoonWorkDir
            )
        );
        $this->trackingManager = new TrackingManagerImpl(
            $this->dataManager, $this->networkManager, $this->visitorManager, $this->clientConfig->getDebugMode(),
        );
        KameleoonLogger::debug(
            "RETURN: new KameleoonClientImpl(siteCode: '%s', clientConfig: %s, " .
            "networkManagerFactory)", $siteCode, $clientConfig,
        );
    }

    /*
     * API Methods (Public Methods)
     */

    public function getVisitorCode(?string $defaultVisitorCode = null, ?int $timeout = null): string
    {
        KameleoonLogger::info("CALL: KameleoonClientImpl->getVisitorCode(defaultVisitorCode: '%s', timeout: %s)",
            $defaultVisitorCode, $timeout);
        if (!isset($this->visitorCode)) {
            if ($defaultVisitorCode !== null) {
                VisitorCodeManager::validateVisitorCode($defaultVisitorCode);
            }
            $this->loadConfiguration($timeout);
            $this->visitorCode = $this->cookieManager->getOrAdd($defaultVisitorCode);
        }
        KameleoonLogger::info(
            "CALL: KameleoonClientImpl->getVisitorCode(defaultVisitorCode: '%s', timeout: %s) -> (visitorCode: '%s')",
            $defaultVisitorCode, $timeout, $this->visitorCode);
        return $this->visitorCode;
    }

    public function addData($visitorCode, ...$data)
    {
        KameleoonLogger::info("CALL: KameleoonClientImpl->addData(visitorCode: '%s', data: %s)",
            $visitorCode, $data);
        VisitorCodeManager::validateVisitorCode($visitorCode);
        $this->loadConfiguration();
        $this->visitorManager->addData($visitorCode, ...$data);
        KameleoonLogger::info("RETURN: KameleoonClientImpl->addData(visitorCode: '%s', data: %s)",
            $visitorCode, $data);
    }

    public function flush(
        $visitorCode = null, ?int $timeout = null, ?bool $isUniqueIdentifier = null, bool $instant = false)
    {
        KameleoonLogger::info(
            "CALL: KameleoonClientImpl->flush(visitorCode: '%s', timeout: %s, isUniqueIdentifier: %s, instant: %s)",
            $visitorCode, $timeout, $isUniqueIdentifier, $instant);
        $this->loadConfiguration($timeout);
        if ($visitorCode !== null) {
            VisitorCodeManager::validateVisitorCode($visitorCode);
            if ($isUniqueIdentifier !== null) {
                $this->setUniqueIdentifier($visitorCode, $isUniqueIdentifier);
            }
            $this->trackingManager->trackVisitor($visitorCode, $instant, $timeout);
        } else {
            $this->trackingManager->trackAll($instant, $timeout);
        }
        KameleoonLogger::info(
            "RETURN: KameleoonClientImpl->flush(visitorCode: '%s', timeout: %s, isUniqueIdentifier: %s, instant: %s)",
            $visitorCode, $timeout, $isUniqueIdentifier, $instant);
    }

    public function trackConversion($visitorCode, int $goalID, $revenue = 0.0, ?int $timeout = null,
        ?bool $isUniqueIdentifier = null)
    {
        KameleoonLogger::info(
            "CALL: KameleoonClientImpl->trackConversion(visitorCode: '%s', goalID: %s, revenue: %s, timeout: %s, " .
            "isUniqueIdentifier: %s)",
            $visitorCode, $goalID, $revenue, $timeout, $isUniqueIdentifier,
        );
        VisitorCodeManager::validateVisitorCode($visitorCode);
        $this->loadConfiguration($timeout);
        if ($isUniqueIdentifier !== null) {
            $this->setUniqueIdentifier($visitorCode, $isUniqueIdentifier);
        }
        $this->addData($visitorCode, new Data\Conversion($goalID));
        $this->trackingManager->trackVisitor($visitorCode);
        KameleoonLogger::info(
            "RETURN: KameleoonClientImpl->trackConversion(visitorCode: '%s', goalID: %s, revenue: %s, timeout: %s, " .
            "isUniqueIdentifier: %s)",
            $visitorCode, $goalID, $revenue, $timeout, $isUniqueIdentifier,
        );
    }

    public function isFeatureActive(string $visitorCode, string $featureKey, ?int $timeout = null,
        ?bool $isUniqueIdentifier = null, bool $track = true): bool
    {
        KameleoonLogger::info(
            "CALL: KameleoonClientImpl->isFeatureActive(visitorCode: '%s', featureKey: '%s', timeout: %s, " .
            "isUniqueIdentifier: %s, track: %s)",
            $visitorCode, $featureKey, $timeout, $isUniqueIdentifier, $track,
        );
        VisitorCodeManager::validateVisitorCode($visitorCode);
        if ($isUniqueIdentifier !== null) {
            $this->setUniqueIdentifier($visitorCode, $isUniqueIdentifier);
        }
        $this->loadConfiguration($timeout);
        try {
            $featureFlag = $this->dataManager->getDataFile()->getFeatureFlag($featureKey);
            [$variationKey, , ] = $this->getVariationInfo($visitorCode, $featureFlag, $track);
            $isFeatureActive = $variationKey != Variation::VARIATION_OFF;
        } catch (FeatureEnvironmentDisabled $ex) {
            KameleoonLogger::debug("Feature environment disabled");
            $isFeatureActive = false;
        }
        if ($track) {
            $this->trackingManager->trackVisitor($visitorCode);
        }
        KameleoonLogger::info(
            "RETURN: KameleoonClientImpl->isFeatureActive(visitorCode: '%s', featureKey: '%s', timeout: %s, " .
            "isUniqueIdentifier: %s, track: %s) -> (isFeatureActive: %s)",
            $visitorCode, $featureKey, $timeout, $isUniqueIdentifier, $track, $isFeatureActive,
        );
        return $isFeatureActive;
    }

    public function getVariation(
        string $visitorCode, string $featureKey, bool $track = true, ?int $timeout = null): Types\Variation
    {
        KameleoonLogger::info(
            "CALL: KameleoonClientImpl->getVariation(visitorCode: '%s', featureKey: '%s', track: %s, timeout: %s)",
            $visitorCode, $featureKey, $track, $timeout,
        );
        VisitorCodeManager::validateVisitorCode($visitorCode);
        $this->loadConfiguration($timeout);
        $featureFlag = $this->dataManager->getDataFile()->getFeatureFlag($featureKey);
        [$variationKey, $varByExp, $rule] = $this->getVariationInfo($visitorCode, $featureFlag, $track);
        $variation = $featureFlag->getVariation($variationKey);
        $externalVariation = self::makeExternalVariation(
            $variationKey, $variation,
            ($varByExp !== null) ? $varByExp->variationId : null,
            ($rule !== null) ? $rule->experimentId : null,
        );
        if ($track) {
            $this->trackingManager->trackVisitor($visitorCode);
        }
        KameleoonLogger::info(
            "RETURN: KameleoonClientImpl->getVariation(visitorCode: '%s', featureKey: '%s', track: %s, timeout: %s)" .
            " -> (variation: %s)",
            $visitorCode, $featureKey, $track, $timeout, $externalVariation,
        );
        return $externalVariation;
    }

    public function getVariations(
        string $visitorCode, bool $onlyActive = false, bool $track = true, ?int $timeout = null): array
    {
        KameleoonLogger::info(
            "CALL: KameleoonClientImpl->getVariations(visitorCode: '%s', onlyActive: %s, track: %s, timeout: %s)",
            $visitorCode, $onlyActive, $track, $timeout,
        );
        VisitorCodeManager::validateVisitorCode($visitorCode);
        $this->loadConfiguration($timeout);
        $variations = array();
        foreach ($this->dataManager->getDataFile()->getFeatureFlags() as $featureFlag) {
            if (!$featureFlag->getEnvironmentEnabled()) {
                continue;
            }
            [$variationKey, $varByExp, $rule] = $this->getVariationInfo($visitorCode, $featureFlag, $track);
            if ($onlyActive && ($variationKey == Variation::VARIATION_OFF)) {
                continue;
            }
            $variation = $featureFlag->getVariation($variationKey);
            $variations[$featureFlag->featureKey] = self::makeExternalVariation(
                $variationKey, $variation,
                ($varByExp !== null) ? $varByExp->variationId : null,
                ($rule !== null) ? $rule->experimentId : null,
            );
        }
        if ($track) {
            $this->trackingManager->trackVisitor($visitorCode);
        }
        KameleoonLogger::info(
            "RETURN: KameleoonClientImpl->getVariations(visitorCode: '%s', onlyActive: %s, track: %s, timeout: %s)" .
            " -> (variations: %s)",
            $visitorCode, $onlyActive, $track, $timeout, $variations,
        );
        return $variations;
    }

    private function getVariationInfo(
        string $visitorCode, FeatureFlag $featureFlag, bool $track = true): array
    {
        KameleoonLogger::debug(
            "CALL: KameleoonClient->getVariationInfo(visitorCode: '%s', featureFlag: %s, track: %s)",
            $visitorCode, $featureFlag, $track,
        );
        $visitor = $this->visitorManager->getVisitor($visitorCode);
        $forcedVariation = ($visitor !== null) ? $visitor->getForcedFeatureVariation($featureFlag->featureKey) : null;
        if ($forcedVariation !== null) {
            $varByExp = $forcedVariation->getVarByExp();
            $rule = $forcedVariation->getRule();
        } else {
            [$varByExp, $rule] = $this->calculateVariationKeyForFeature($visitorCode, $featureFlag);
        }
        if (($forcedVariation === null) || !$forcedVariation->isSimulated()) {
            $this->saveVariation($visitorCode, $rule, $varByExp, $track);
        }
        $variationKey = $this->calculateVariationKey($varByExp, $rule, $featureFlag->defaultVariationKey);
        KameleoonLogger::debug(
            "RETURN: KameleoonClient->getVariationInfo(visitorCode: '%s', featureFlag: %s, track: %s)" .
            " -> (variationKey: '%s', variationByExposition: %s, rule: %s)",
            $visitorCode, $featureFlag, $track, $variationKey, $varByExp, $rule,
        );
        return [$variationKey, $varByExp, $rule];
    }

    private static function makeExternalVariation(
        string $variationKey, ?Variation $internalVariation, ?int $variationId, ?int $experimentId): Types\Variation
    {
        KameleoonLogger::debug(
            "CALL: KameleoonClient::makeExternalVariation(variationKey: '%s', internalVariation: %s, " .
            "variationId: %s, experimentId: %s)",
            $variationKey, $internalVariation, $variationId, $experimentId
        );
        $variables = array();
        if ($internalVariation !== null) {
            foreach ($internalVariation->variables as $variable) {
                $variables[$variable->key] = new Variable($variable->key, $variable->type, $variable->getValue());
            }
        }
        $variation = new Types\Variation($variationKey, $variationId, $experimentId, $variables);
        KameleoonLogger::debug(
            "RETURN: KameleoonClient::makeExternalVariation(variationKey: '%s', internalVariation: %s, " .
            "variationId: %s, experimentId: %s) -> (variation: %s)",
            $variationKey, $internalVariation, $variationId, $experimentId, $variation
        );
        return $variation;
    }

    /**
     * @deprecated deprecated since version 4.5.0. Please use `getVariation($visitorCode, $featureKey, true)`
     */
    public function getFeatureVariationKey(string $visitorCode, string $featureKey, ?int $timeout = null,
        ?bool $isUniqueIdentifier = null): string
    {
        KameleoonLogger::info(
            "Call of deprecated method 'getFeatureVariationKey'. " .
            "Please, use 'getVariation(\$visitorCode, \$featureKey, true)' instead."
        );
        KameleoonLogger::info(
            "CALL: KameleoonClientImpl->isFeatureActive(visitorCode: '%s', featureKey: '%s', timeout: %s, " .
            "isUniqueIdentifier: %s)",
            $visitorCode, $featureKey, $timeout, $isUniqueIdentifier,
        );
        VisitorCodeManager::validateVisitorCode($visitorCode);
        if ($isUniqueIdentifier !== null) {
            $this->setUniqueIdentifier($visitorCode, $isUniqueIdentifier);
        }
        [, $variationKey] = $this->getFeatureVariationKeyInternal($visitorCode, $featureKey, $timeout);
        KameleoonLogger::info(
            "RETURN: KameleoonClientImpl->isFeatureActive(visitorCode: '%s', featureKey: '%s', timeout: %s, " .
            "isUniqueIdentifier: %s) -> (variationKey: '%s')",
            $visitorCode, $featureKey, $timeout, $isUniqueIdentifier, $variationKey,
        );
        return $variationKey;
    }

    /**
     * @deprecated deprecated since version 4.5.0. Please use `getVariation($visitorCode, $featureKey, true)`
     */
    public function getFeatureVariable(
        string $visitorCode,
        string $featureKey,
        string $variableName,
        ?int $timeout = null,
        ?bool $isUniqueIdentifier = null)
    {
        KameleoonLogger::info(
            "Call of deprecated method 'getFeatureVariable'. " .
            "Please, use 'getVariation(\$visitorCode, \$featureKey, true)' instead."
        );
        KameleoonLogger::info(
            "CALL: KameleoonClientImpl->getFeatureVariable(visitorCode: '%s', featureKey: '%s', " .
            "variableName: '%s', timeout: %s, isUniqueIdentifier: %s)",
            $visitorCode, $featureKey, $variableName, $timeout, $isUniqueIdentifier,
        );
        VisitorCodeManager::validateVisitorCode($visitorCode);
        if ($isUniqueIdentifier !== null) {
            $this->setUniqueIdentifier($visitorCode, $isUniqueIdentifier);
        }
        [$featureFlag, $variationKey] = $this->getFeatureVariationKeyInternal($visitorCode, $featureKey, $timeout);
        $variation = $featureFlag->getVariation($variationKey);
        if (is_null($variation)) {
            KameleoonLogger::info(
                "RETURN: KameleoonClientImpl->getFeatureVariable(visitorCode: '%s', featureKey: '%s', " .
                "variableName: '%s', timeout: %s, isUniqueIdentifier: %s) -> (variable: null)",
                $visitorCode, $featureKey, $variableName, $timeout, $isUniqueIdentifier,
            );
            return null;
        }
        $variable = $variation->getVariable($variableName);
        if (!is_null($variable)) {
            $value = $variable->getValue();
            KameleoonLogger::info(
                "RETURN: KameleoonClientImpl->getFeatureVariable(visitorCode: '%s', featureKey: '%s', " .
                "variableName: '%s', timeout: %s, isUniqueIdentifier: %s) -> (variable: %s)",
                $visitorCode, $featureKey, $variableName, $timeout, $isUniqueIdentifier, $value,
            );
            return $value;
        } else {
            throw new FeatureVariableNotFound("Feature variable {$variableName} not found");
        }
    }

    /**
     * @deprecated deprecated since version 4.5.0. Please use `getVariation($visitorCode, $featureKey, false)`
     */
    public function getFeatureVariationVariables(string $featureKey, string $variationKey, ?int $timeout = null): array
    {
        KameleoonLogger::info(
            "Call of deprecated method 'getFeatureVariationVariables'. " .
            "Please, use 'getVariation(\$visitorCode, \$featureKey, false)' instead."
        );
        KameleoonLogger::info(
            "CALL: KameleoonClientImpl->getFeatureVariationVariables(featureKey: '%s', variationKey: '%s', " .
            "timeout: %s)",
            $featureKey, $variationKey, $timeout,
        );
        $this->loadConfiguration($timeout);
        $featureFlag = $this->dataManager->getDataFile()->getFeatureFlag($featureKey);
        $variation = $featureFlag->getVariation($variationKey);
        if (is_null($variation)) {
            throw new FeatureVariationNotFound("Variation key {$variationKey} not found");
        }
        $variables = array_map(fn($var) => $var->getValue(), $variation->variables);
        KameleoonLogger::info(
            "RETURN: KameleoonClientImpl->getFeatureVariationVariables(featureKey: '%s', variationKey: '%s', " .
            "timeout: %s) -> (variables: %s)",
            $featureKey, $variationKey, $timeout, $variables,
        );
        return $variables;
    }

    public function getFeatureList(?int $timeout = null): array
    {
        KameleoonLogger::info("CALL: KameleoonClientImpl->getFeatureList(timeout: %s)", $timeout);
        $this->loadConfiguration($timeout);
        $features = array_keys($this->dataManager->getDataFile()->getFeatureFlags());
        KameleoonLogger::info(
            "RETURN: KameleoonClientImpl->getFeatureList(timeout: %s) -> (features: %s)", $timeout, $features);
        return $features;
    }

    /**
     * @deprecated deprecated since version 4.3.0. Please use `getActiveFeatures`
     */
    public function getActiveFeatureListForVisitor(string $visitorCode, ?int $timeout = null): array
    {
        KameleoonLogger::info(
            "Call of deprecated method 'getActiveFeatureListForVisitor'. Please, use 'getActiveFeatures' instead."
        );
        KameleoonLogger::info(
            "CALL: KameleoonClientImpl->getActiveFeatureListForVisitor(visitorCode: '%s', timeout: %s)",
            $visitorCode, $timeout,
        );
        VisitorCodeManager::validateVisitorCode($visitorCode);
        $arrayKeys = array();
        $this->loadConfiguration($timeout);
        $visitor = $this->visitorManager->getVisitor($visitorCode);
        foreach ($this->dataManager->getDataFile()->getFeatureFlags() as $featureFlag) {
            $forcedVariation = ($visitor !== null) ?
                $visitor->getForcedFeatureVariation($featureFlag->featureKey) : null;
            if ($forcedVariation !== null) {
                $variation = $forcedVariation->getVarByExp();
                $rule = $forcedVariation->getRule();
            } else {
                [$variation, $rule] = $this->calculateVariationKeyForFeature($visitorCode, $featureFlag);
            }
            $variationKey = $this->calculateVariationKey($variation, $rule, $featureFlag->defaultVariationKey);
            if ($variationKey != Variation::VARIATION_OFF) {
                $arrayKeys[] = $featureFlag->featureKey;
            }
        }
        KameleoonLogger::info(
            "RETURN: KameleoonClientImpl->getActiveFeatureListForVisitor(visitorCode: '%s', timeout: %s)" .
            " -> (arrayKeys: %s)", $visitorCode, $timeout, $arrayKeys,
        );
        return $arrayKeys;
    }

    /**
     * @deprecated deprecated since version 4.5.0. Please use `getVariations($visitorCode, true, false)`
     */
    public function getActiveFeatures(string $visitorCode, ?int $timeout = null): array
    {
        KameleoonLogger::info(
            "Call of deprecated method 'getActiveFeatures'. " .
            "Please, use 'getVariations(\$visitorCode, true, false)' instead."
        );
        KameleoonLogger::info(
            "CALL: KameleoonClientImpl->getActiveFeatures(visitorCode: '%s', timeout: %s)",
            $visitorCode, $timeout,
        );
        VisitorCodeManager::validateVisitorCode($visitorCode);
        $mapActiveFeatures = array();
        $this->loadConfiguration($timeout);
        $visitor = $this->visitorManager->getVisitor($visitorCode);
        foreach ($this->dataManager->getDataFile()->getFeatureFlags() as $featureFlag) {
            if (!$featureFlag->getEnvironmentEnabled()) {
                continue;
            }
            $forcedVariation = ($visitor !== null) ?
                $visitor->getForcedFeatureVariation($featureFlag->featureKey) : null;
            if ($forcedVariation !== null) {
                $varByExp = $forcedVariation->getVarByExp();
                $rule = $forcedVariation->getRule();
            } else {
                [$varByExp, $rule] = $this->calculateVariationKeyForFeature($visitorCode, $featureFlag);
            }
            $variationKey = $this->calculateVariationKey($varByExp, $rule, $featureFlag->defaultVariationKey);
            if ($variationKey == Variation::VARIATION_OFF) {
                continue;
            }
            $variation = $featureFlag->getVariation($variationKey);
            $variables = array();
            if ($variation !== null) {
                foreach ($variation->variables as $key => $variable) {
                    $variables[$key] = new Variable($key, $variable->type, $variable->getValue());
                }
            }
            $mapActiveFeatures[$featureFlag->featureKey] = new Types\Variation(
                $variationKey,
                ($varByExp !== null) ? $varByExp->variationId : null,
                ($rule !== null) ? $rule->experimentId : null,
                $variables
            );
        }
        KameleoonLogger::info(
            "RETURN: KameleoonClientImpl->getActiveFeatures(visitorCode: '%s', timeout: %s) -> (activeFeatures: %s)",
            $visitorCode, $timeout, $mapActiveFeatures);
        return $mapActiveFeatures;
    }

    public function getEngineTrackingCode(string $visitorCode): string
    {
        KameleoonLogger::info("CALL: KameleoonClientImpl->getEngineTrackingCode(visitorCode: '%s')", $visitorCode);
        $visitor = $this->visitorManager->getVisitor($visitorCode);
        if (!isset($this->hybridManager)) {
            $this->hybridManager = new HybridManagerImpl($this->dataManager);
        }
        $trackingCode = $this->hybridManager->getEngineTrackingCode(
            !is_null($visitor) ? $visitor->getAssignedVariations() : null
        );
        KameleoonLogger::info(
            "RETURN: KameleoonClientImpl->getEngineTrackingCode(visitorCode: '%s') -> (trackingCode: %s)",
            $visitorCode, $trackingCode,
        );
        return $trackingCode;
    }

    public function getRemoteData(string $key, ?int $timeout = null)
    {
        KameleoonLogger::info("CALL: KameleoonClientImpl->getRemoteData(key: '%s', timeout: %s)", $key, $timeout);
        $remoteData = $this->getRemoteDataManager()->getData($key, $timeout);
        KameleoonLogger::info(
            function () use ($key, $timeout, $remoteData) {
                return sprintf(
                    "RETURN: KameleoonClientImpl->getRemoteData(key: '%s', timeout: %s) -> (remoteData: %s)",
                    $key, $timeout, json_encode($remoteData),
                );
            }
        );
        return $remoteData;
    }

    public function getRemoteVisitorData(string $visitorCode, ?int $timeout = null, bool $addData = true,
        ?RemoteVisitorDataFilter $filter = null, ?bool $isUniqueIdentifier = null): array
    {
        KameleoonLogger::info(
            "CALL: KameleoonClientImpl->getRemoteVisitorData(visitorCode: '%s', timeout: %s, addData: %s, " .
            "filter: %s, isUniqueIdentifier: %s)",
            $visitorCode, $timeout, $addData, $filter, $isUniqueIdentifier,
        );
        $this->loadConfiguration($timeout);
        if ($isUniqueIdentifier !== null) {
            $this->setUniqueIdentifier($visitorCode, $isUniqueIdentifier);
        }
        $visitorData = $this->getRemoteDataManager()->getVisitorData($visitorCode, $timeout, $filter, $addData);
        KameleoonLogger::info(
            function () use ($visitorCode, $timeout, $addData, $filter, $isUniqueIdentifier, $visitorData) {
                return sprintf(
                    "RETURN: KameleoonClientImpl->getRemoteVisitorData(visitorCode: '%s', timeout: %s, addData: %s, " .
                    "filter: %s, isUniqueIdentifier: %s) -> (visitorData: %s)",
                    $visitorCode, $timeout, $addData, $filter, $isUniqueIdentifier, json_encode($visitorData),
                );
            }
        );
        return $visitorData;
    }

    public function getVisitorWarehouseAudience(
        string $visitorCode,
        int $customDataIndex,
        ?string $warehouseKey = null,
        ?int $timeout = null
    ): ?CustomData
    {
        KameleoonLogger::info(
            "CALL: KameleoonClientImpl->getVisitorWarehouseAudience(visitorCode: '%s', customDataIndex: %s, " .
            "warehouseKey: '%s', timeout: %s)",
            $visitorCode, $customDataIndex, $warehouseKey, $timeout,
        );
        if (!isset($this->warehouseManager)) {
            $this->warehouseManager = new WarehouseManagerImpl($this->networkManager, $this->visitorManager);
        }
        $customData = $this->warehouseManager->getVisitorWarehouseAudience(
            $visitorCode,
            $customDataIndex,
            $warehouseKey,
            $timeout
        );
        KameleoonLogger::info(
            "RETURN: KameleoonClientImpl->getVisitorWarehouseAudience(visitorCode: '%s', customDataIndex: %s, " .
            "warehouseKey: '%s', timeout: %s) -> (customData: %s)",
            $visitorCode, $customDataIndex, $warehouseKey, $timeout, $customData,
        );
        return $customData;
    }

    public function setLegalConsent(string $visitorCode, bool $legalConsent): void
    {
        KameleoonLogger::info(
            "CALL: KameleoonClientImpl->setLegalConsent(visitorCode: '%s', legalConsent: %s)",
            $visitorCode, $legalConsent);
        VisitorCodeManager::validateVisitorCode($visitorCode);
        $this->visitorManager->getOrCreateVisitor($visitorCode)->setLegalConsent($legalConsent);
        $this->cookieManager->update($visitorCode, $legalConsent);
        KameleoonLogger::info(
            "RETURN: KameleoonClientImpl->setLegalConsent(visitorCode: '%s', legalConsent: %s)",
            $visitorCode, $legalConsent);
    }

    /*
     * Helper Methods (Private Methods)
     */

    private function getFeatureVariationKeyInternal(
        string $visitorCode,
        string $featureKey,
        ?int $timeout = null): array
    {
        KameleoonLogger::debug(
            "CALL: KameleoonClientImpl->getFeatureVariationKeyInternal(visitorCode: '%s', featureKey: '%s', " .
            "timeout: %s)", $visitorCode, $featureKey, $timeout,
        );
        $this->loadConfiguration($timeout);
        VisitorCodeManager::validateVisitorCode($visitorCode);
        $featureFlag = $this->dataManager->getDataFile()->getFeatureFlag($featureKey);
        $visitor = $this->visitorManager->getVisitor($visitorCode);
        $forcedVariation = ($visitor !== null) ? $visitor->getForcedFeatureVariation($featureKey) : null;
        if ($forcedVariation !== null) {
            $variation = $forcedVariation->getVarByExp();
            $rule = $forcedVariation->getRule();
        } else {
            [$variation, $rule] = $this->calculateVariationKeyForFeature($visitorCode, $featureFlag);
        }
        if (($forcedVariation === null) || !$forcedVariation->isSimulated()) {
            $this->saveVariation($visitorCode, $rule, $variation);
        }
        $variationKey = $this->calculateVariationKey($variation, $rule, $featureFlag->defaultVariationKey);
        $this->trackingManager->trackVisitor($visitorCode);
        KameleoonLogger::debug(
            "RETURN: KameleoonClientImpl->getFeatureVariationKeyInternal(visitorCode: '%s', featureKey: '%s', " .
            "timeout: %s) -> (featureFlag: %s, variationKey: '%s')",
            $visitorCode, $featureKey, $timeout, $featureFlag, $variationKey,
        );
        return [$featureFlag, $variationKey];
    }

    private function calculateVariationKey(
        ?VariationByExposition $varByExp,
        ?Rule $rule,
        string $defaultVariationKey
    ): string
    {
        KameleoonLogger::debug(
            "CALL: KameleoonClientImpl->calculateVariationKey(varByExp: %s, rule: %s, defaultVariationKey: '%s')",
            $varByExp, $rule, $defaultVariationKey);
        if ($varByExp != null) {
            $variationKey = $varByExp->variationKey;
        } else {
            if ($rule != null && $rule->isExperiment()) {
                $variationKey = Variation::VARIATION_OFF;
            } else {
                $variationKey = $defaultVariationKey;
            }
        }
        KameleoonLogger::debug(
            "RETURN: KameleoonClientImpl->calculateVariationKey(varByExp: %s, rule: %s, defaultVariationKey: '%s')" .
            " -> (variationKey: '%s')", $varByExp, $rule, $defaultVariationKey, $variationKey,
        );
        return $variationKey;
    }

    private function saveVariation(
        string $visitorCode,
        ?Rule $rule,
        ?VariationByExposition $varByExp,
        bool $track = true): void
    {
        $experimentId = ($rule !== null) ? $rule->experimentId : null;
        $variationId = ($varByExp !== null) ? $varByExp->variationId : null;
        if (($experimentId === null) || ($variationId === null)) {
            return;
        }
        KameleoonLogger::debug(
            "CALL: KameleoonClientImpl->saveVariation(visitorCode: '%s', rule: %s, varByExp: %s, track: %s)",
            $visitorCode, $rule, $varByExp, $track,
        );
        $ruleType = AssignedVariation::convertLiteralRuleTypeToEnum($rule->type);
        $asVariation = new AssignedVariation($experimentId, $variationId, $ruleType);
        if (!$track) {
            $asVariation->markAsSent();
        }
        $this->visitorManager->addData($visitorCode, $asVariation);
        KameleoonLogger::debug(
            "RETURN: KameleoonClientImpl->saveVariation(visitorCode: '%s', rule: %s, varByExp: %s, track: %s)",
            $visitorCode, $rule, $varByExp, $track,
        );
    }

    private function shouldDataFileBeUpdated(): bool
    {
        return !file_exists($this->configurationFilePath) ||
            (time() >= filemtime($this->configurationFilePath) + $this->clientConfig->getRefreshIntervalSecond());
    }

    private function getTimeout(?int $timeout = null): int
    {
        return $timeout ?? $this->clientConfig->getDefaultTimeoutMillisecond();
    }

    //load configuration if it was not loaded
    private function loadConfiguration(?int $timeout = null)
    {
        KameleoonLogger::debug("CALL: KameleoonClientImpl->loadConfiguration(timeout: %s)", $timeout);
        $dataFileShouldBeUpdated = $this->shouldDataFileBeUpdated();
        $dataFile = $this->dataManager->getDataFile();
        if (($dataFile == null) || $dataFileShouldBeUpdated) {
            if ($dataFileEmpty = !file_exists($this->configurationFilePath)) {
                $fp = fopen($this->configurationFilePath, "a");
                fclose($fp);
            }
            $fp = fopen($this->configurationFilePath, "r+");
            if ($dataFileShouldBeUpdated && flock($fp, LOCK_EX)) {
                $this->updateConfiguration($this->getTimeout($timeout), $dataFileEmpty);
                flock($fp, LOCK_UN);
            } else {
                try {
                    $this->loadDataFileLocal();
                } catch (DataFileInvalid $e) {
                    if (flock($fp, LOCK_EX)) {
                        $this->updateConfiguration($this->getTimeout($timeout), true);
                        flock($fp, LOCK_UN);
                    }
                }
            }
            $dataFile = $this->dataManager->getDataFile();
        }
        KameleoonLogger::debug("RETURN: KameleoonClientImpl->loadConfiguration(timeout: %s)", $timeout);
    }

    private function applyNewConfiguration($dataFileJson)
    {
        KameleoonLogger::debug("CALL: KameleoonClientImpl->applyNewConfiguration(dataFileJson: %s)", $dataFileJson);
        $this->dataManager->setDataFile(new DataFile($dataFileJson, $this->clientConfig->getEnvironment()));
        $settings = $this->dataManager->getDataFile()->getSettings();
        $this->networkManager->getUrlProvider()->applyDataApiDomain($settings->getDataApiDomain());
        KameleoonLogger::debug("RETURN: KameleoonClientImpl->applyNewConfiguration(dataFileJson: %s)", $dataFileJson);
    }

    private function loadDataFileLocal()
    {
        KameleoonLogger::debug("CALL: KameleoonClientImpl->loadDataFileLocal()");
        $dataFileJsonLocal = json_decode(file_get_contents($this->configurationFilePath, true));
        if ($dataFileJsonLocal === null) {
            throw new DataFileInvalid("Local data file is invalid");
        }
        $this->applyNewConfiguration($dataFileJsonLocal);
        KameleoonLogger::debug("RETURN: KameleoonClientImpl->loadDataFileLocal()");
    }

    private function updateConfiguration(int $timeout, bool $forceNetworkRequest = false)
    {
        KameleoonLogger::debug(
            "CALL: KameleoonClientImpl->updateConfiguration(timeout: %s, forceNetworkRequest: %s)",
            $timeout, $forceNetworkRequest);
        try {
            if ($this->shouldDataFileBeUpdated() || $forceNetworkRequest) {
                $dataFileOutput = $this->networkManager->fetchConfiguration($timeout);
                $dataFileJsonRemote = ($dataFileOutput !== null) ? json_decode($dataFileOutput) : null;
                if (isset($dataFileJsonRemote->featureFlags)) {
                    file_put_contents($this->configurationFilePath, $dataFileOutput);
                    $this->applyNewConfiguration($dataFileJsonRemote);
                } else {
                    $this->loadDataFileLocal();
                }
            } else {
                $this->loadDataFileLocal();
            }
        } catch (DataFileInvalid $e) {
            throw new DataFileInvalid("Data file is invalid: " . $e->getMessage());
        } catch (Exception $e) {
            KameleoonLogger::error(
                "Saved data file will be used. The file needs to be updated, but an error occurred: " . $e->getMessage()
            );
            $this->loadDataFileLocal();
        } finally {
            $this->updateConfigurationFileModificationTime();
        }
        KameleoonLogger::debug(
            "RETURN: KameleoonClientImpl->updateConfiguration(timeout: %s, forceNetworkRequest: %s)",
            $timeout, $forceNetworkRequest);
    }

    private function updateConfigurationFileModificationTime()
    {
        KameleoonLogger::debug("CALL: KameleoonClientImpl->updateConfigurationFileModificationTime()");
        if (file_exists($this->configurationFilePath)) {
            touch($this->configurationFilePath);
            clearstatcache();
        }
        KameleoonLogger::debug("RETURN: KameleoonClientImpl->updateConfigurationFileModificationTime()");
    }

    private function calculateVariationKeyForFeature(string $visitorCode, FeatureFlag $featureFlag): array
    {
        KameleoonLogger::debug(
            "CALL: KameleoonClientImpl->calculateVariationKeyForFeature(visitorCode: '%s', featureFlag: %s)",
            $visitorCode, $featureFlag);
        // use mappingIdentifier instead of visitorCode if it was set up
        $visitor = $this->visitorManager->getVisitor($visitorCode);
        $mappingIdentifier = ($visitor !== null) ? $visitor->getMappingIdentifier() : null;
        $codeForHash = ($mappingIdentifier === null) ? $visitorCode : $mappingIdentifier;
        $selectedVariation = null;
        $selectedRule = null;
        // no rules -> return defaultVariationKey
        foreach ($featureFlag->rules as $rule) {
            $forcedVariation = ($visitor !== null) ? $visitor->getForcedExperimentVariation($rule->experimentId) : null;
            if (($forcedVariation !== null) && $forcedVariation->isForceTargeting()) {
                // Forcing experiment variation in force-targeting mode
                $selectedVariation = $forcedVariation->getVarByExp();
                $selectedRule = $rule;
                break;
            }
            // check if visitor is targeted for rule, else next rule
            if ($this->targetingManager->checkTargeting($visitorCode, $rule->experimentId, $rule)) {
                if ($forcedVariation !== null) {
                    // Forcing experiment variation in targeting-only mode
                    $selectedVariation = $forcedVariation->getVarByExp();
                    $selectedRule = $rule;
                    break;
                }
                // uses for rule exposition
                $hashRule = HashDouble::obtain($codeForHash, $rule->id, $rule->respoolTime);
                KameleoonLogger::debug("Calculated hashRule: %s for visitorCode: '%s'", $hashRule, $codeForHash);
                // check main expostion for rule with hashRule
                if ($hashRule <= $rule->exposition) {
                    if ($rule->isTargetedDelivery() && count($rule->variationByExposition) > 0) {
                        $selectedVariation = $rule->variationByExposition[0];
                        $selectedRule = $rule;
                        break;
                    }
                    // uses for variation's expositions
                    $hashVariation = HashDouble::obtain($codeForHash, $rule->experimentId, $rule->respoolTime);
                    KameleoonLogger::debug(
                        "Calculated hashVariation: %s for visitorCode: '%s'", $hashVariation, $codeForHash);
                    // get variation key with new hashVariation
                    $variation = $this->calculateVariatonRuleHash($rule, $hashVariation);
                    // variation can be null for experiment rules only, for targeted rule will be always exist
                    if (!is_null($variation)) {
                        $selectedVariation = $variation;
                        $selectedRule = $rule;
                        break;
                    }
                } elseif ($rule->isTargetedDelivery()) {
                    // if visitor is targeted but not bucketed for targeted rule then break cycle -> return default
                    break;
                }
            }
        }
        KameleoonLogger::debug(
            "RETURN: KameleoonClientImpl->calculateVariationKeyForFeature(visitorCode: '%s', featureFlag: %s)" .
            " -> (variation: %s, rule: %s)", $visitorCode, $featureFlag, $selectedVariation, $selectedRule
        );
        return [$selectedVariation, $selectedRule];
    }

    private function calculateVariatonRuleHash(Rule $rule, float $hash): ?VariationByExposition
    {
        KameleoonLogger::debug(
            "CALL: KameleoonClientImpl->calculateVariatonRuleHash(rule: %s, hash: %s)",
            $rule, $hash);
        $total = 0.0;
        foreach ($rule->variationByExposition as $variationByExposition) {
            $total += $variationByExposition->exposition;
            if ($total >= $hash) {
                KameleoonLogger::debug(
                    "RETURN: KameleoonClientImpl->calculateVariatonRuleHash(rule: %s, hash: %s) -> (variation: %s)",
                    $rule, $hash, $variationByExposition);
                return $variationByExposition;
            }
        }
        KameleoonLogger::debug(
            "RETURN: KameleoonClientImpl->calculateVariatonRuleHash(rule: %s, hash: %s) -> (variation: null)",
            $rule, $hash);
        return null;
    }

    private function getRemoteDataManager(): RemoteDataManager
    {
        if (!isset($this->remoteDataManager)) {
            $this->remoteDataManager =
                new RemoteDataManagerImpl($this->dataManager, $this->networkManager, $this->visitorManager);
        }
        return $this->remoteDataManager;
    }

    private function setUniqueIdentifier(string $visitorCode, bool $isUniqueIdentifier): void
    {
        KameleoonLogger::info(
            "The 'isUniqueIdentifier' parameter is deprecated. Please, add 'UniqueIdentifier' to a visitor instead."
        );
        $this->visitorManager->addData($visitorCode, new Data\UniqueIdentifier($isUniqueIdentifier));
    }

    public function setForcedVariation(
        string $visitorCode, int $experimentId, ?string $variationKey, bool $forceTargeting = true, ?int $timeout = null
    ): void
    {
        KameleoonLogger::info(
            "CALL: KameleoonClientImpl.SetForcedVariation(visitorCode: '%s', experimentId: %s," .
            " variationKey: %s, forceTargeting: %s)",
            $visitorCode, $experimentId, ($variationKey === null) ? "null" : "'$variationKey'", $forceTargeting
        );
        VisitorCodeManager::validateVisitorCode($visitorCode);
        $this->loadConfiguration($timeout);
        if ($variationKey !== null) {
            $ruleInfo = $this->dataManager->getDataFile()->getRuleInfoByExpId($experimentId);
            if ($ruleInfo === null) {
                throw new FeatureExperimentNotFound("Experiment $experimentId is not found");
            }
            $rule = $ruleInfo->getRule();
            $forcedVariation = new ForcedExperimentVariation(
                $rule, $rule->getVariationByKey($variationKey), $forceTargeting
            );
            $this->visitorManager->addData($visitorCode, $forcedVariation);
        } else {
            $visitor = $this->visitorManager->getVisitor($visitorCode);
            if ($visitor !== null) {
                $visitor->resetForcedExperimentVariation($experimentId);
            }
        }
        KameleoonLogger::info(
            "RETURN: KameleoonClientImpl.SetForcedVariation(visitorCode: '%s', experimentId: %s," .
            " variationKey: %s, forceTargeting: %s)",
            $visitorCode, $experimentId, ($variationKey === null) ? "null" : "'$variationKey'", $forceTargeting
        );
    }
}
