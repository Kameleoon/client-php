<?php

namespace Kameleoon;

use Exception;
use Generator;
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

use Kameleoon\Configuration\DataFile;
use Kameleoon\Configuration\FeatureFlag;
use Kameleoon\Configuration\Rule;
use Kameleoon\Configuration\Variation;
use Kameleoon\Configuration\VariationByExposition;
use Kameleoon\Data\CustomData;
use Kameleoon\Data\Manager\AssignedVariation;
use Kameleoon\Data\Manager\Visitor;
use Kameleoon\Data\Manager\VisitorManager;
use Kameleoon\Data\Manager\VisitorManagerImpl;
use Kameleoon\Exception\DataFileInvalid;
use Kameleoon\Exception\FeatureEnvironmentDisabled;
use Kameleoon\Exception\FeatureVariableNotFound;
use Kameleoon\Exception\FeatureVariationNotFound;
use Kameleoon\Exception\SiteCodeIsEmpty;
use Kameleoon\Helpers\HashDouble;
use Kameleoon\Helpers\SdkVersion;
use Kameleoon\Helpers\VisitorCodeManager;
use Kameleoon\Managers\Hybrid\HybridManager;
use Kameleoon\Managers\Hybrid\HybridManagerImpl;
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

class KameleoonClientImpl implements KameleoonClient
{
    const FILE_CONFIGURATION_NAME = "kameleoon-configuration-";
    const VISITOR_CODE_MAX_LENGTH = 255;

    private DataFile $dataFile;
    private KameleoonClientConfig $clientConfig;
    private string $configurationFilePath;
    private string $visitorCode;

    private VisitorManager $visitorManager;
    private NetworkManager $networkManager;
    private CookieManager $cookieManager;
    private HybridManager $hybridManager;
    private WarehouseManager $warehouseManager;

    public function __construct(
        string $siteCode,
        KameleoonClientConfig $clientConfig,
        ?VisitorManager $visitorManager = null,
        ?NetworkManagerFactory $networkManagerFactory = null
    ) {
        if (empty($siteCode)) {
            throw new SiteCodeIsEmpty("Provided siteCode is empty");
        }

        $this->visitorManager = $visitorManager ?? new VisitorManagerImpl();
        $this->cookieManager = new CookieManagerImpl($clientConfig->getCookieOptions());

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
    }

    /*
     * API Methods (Public Methods)
     */

    public function getVisitorCode(?string $defaultVisitorCode = null, ?int $timeout = null): string
    {
        if (!isset($this->visitorCode)) {
            if ($defaultVisitorCode !== null) {
                VisitorCodeManager::validateVisitorCode($defaultVisitorCode);
            }
            $this->loadConfiguration($timeout);
            $this->visitorCode = $this->cookieManager->getOrAdd($defaultVisitorCode);
        }
        return $this->visitorCode;
    }

    public function addData($visitorCode, ...$data)
    {
        VisitorCodeManager::validateVisitorCode($visitorCode);
        $this->visitorManager->getOrCreateVisitor($visitorCode)->addData(...$data);
    }

    public function flush($visitorCode = null, ?int $timeout = null)
    {
        $this->loadConfiguration($timeout);
        if ($visitorCode !== null) {
            VisitorCodeManager::validateVisitorCode($visitorCode);
            $this->sendTrackingRequest($visitorCode);
        } else {
            foreach ($this->visitorManager as $user) {
                $this->flush($user, $timeout);
            }
        }
    }

    public function trackConversion($visitorCode, int $goalID, $revenue = 0.0, ?int $timeout = null)
    {
        $this->addData($visitorCode, new Data\Conversion($goalID));
        $this->flush($visitorCode, $timeout);
    }

    public function isFeatureActive(string $visitorCode, string $featureKey, ?int $timeout = null): bool
    {
        try {
            [, $variationKey] = $this->getFeatureVariationKeyInternal($visitorCode, $featureKey, $timeout);
            return $variationKey != Variation::VARIATION_OFF;
        } catch (FeatureEnvironmentDisabled $ex) {
            return false;
        }
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

    public function getFeatureVariationVariables(string $featureKey, string $variationKey, ?int $timeout = null): array
    {
        $this->loadConfiguration($timeout);
        $featureFlag = $this->dataFile->getFeatureFlag($featureKey);
        $variation = $featureFlag->getVariation($variationKey);
        if (is_null($variation)) {
            throw new FeatureVariationNotFound("Variation key {$variationKey} not found");
        }
        return array_map(fn ($var) => $var->getValue(), $variation->variables);
    }

    public function getFeatureList(?int $timeout = null): array
    {
        $this->loadConfiguration($timeout);
        return array_keys($this->dataFile->getFeatureFlags());
    }

    public function getActiveFeatureListForVisitor(string $visitorCode, ?int $timeout = null): array
    {
        VisitorCodeManager::validateVisitorCode($visitorCode);
        $arrayKeys = array();
        $this->loadConfiguration($timeout);
        foreach ($this->dataFile->getFeatureFlags() as $featureFlag) {
            [$variation, $rule] = $this->calculateVariationKeyForFeature($visitorCode, $featureFlag);
            $variationKey = $this->calculateVariationKey($variation, $rule, $featureFlag->defaultVariationKey);
            if ($variationKey != Variation::VARIATION_OFF) {
                $arrayKeys[] = $featureFlag->featureKey;
            }
        }
        return $arrayKeys;
    }

    public function getEngineTrackingCode(string $visitorCode): string
    {
        $visitor = $this->visitorManager->getVisitor($visitorCode);
        if (!isset($this->hybridManager)) {
            $this->hybridManager = new HybridManagerImpl();
        }
        return $this->hybridManager->getEngineTrackingCode(
            !is_null($visitor) ? $visitor->getAssignedVariations() : null
        );
    }


    public function getRemoteData(string $key, ?int $timeout = null)
    {
        $data = $this->networkManager->getRemoteData($key, $timeout);
        if ($data === null) {
            error_log("Get remote data failed");
        }
        return $data;
    }

    public function getRemoteVisitorData(string $visitorCode, ?int $timeout = null, bool $addData = true): array
    {
        $json = $this->networkManager->getRemoteVisitorData($visitorCode, $timeout);
        if ($json === null) {
            error_log("Get remote visitor data failed");
            return [];
        }
        $customDataList = $this->parseCustomDataList($json);
        if ($addData) {
            $this->addData($visitorCode, ...$customDataList);
        }
        return $customDataList;
    }
    private function parseCustomDataList($json): array
    {
        $latestRecord = $json->currentVisit ?? null;
        if ($latestRecord === null) {
            $previousVisits = $json->previousVisits ?? null;
            if ($previousVisits === null || empty($previousVisits)) {
                return [];
            }
            $latestRecord = $previousVisits[0];
        }
        $customDataEvents = $latestRecord->customDataEvents ?? null;
        return ($customDataEvents !== null) ? $this->parseCustomDataListFromEvents($customDataEvents) : [];
    }
    private function parseCustomDataListFromEvents($customDataEvents): array
    {
        $customDataList = [];
        foreach ($customDataEvents as $event) {
            $customDataJson = $event->data ?? null;
            if ($customDataJson === null) {
                continue;
            }
            $customDataList[] = $this->parseCustomData($customDataJson);
        }
        return $customDataList;
    }

    private function parseCustomData($json): CustomData
    {
        $id = $json->index ?? -1;
        $valuesCountMap = (array)$json->valuesCountMap ?? [];
        $values = array_keys($valuesCountMap);
        return new CustomData($id, ...$values);
    }

    public function setLegalConsent(string $visitorCode, bool $legalConsent): void
    {
        VisitorCodeManager::validateVisitorCode($visitorCode);
        $this->visitorManager->getOrCreateVisitor($visitorCode)->setLegalConsent($legalConsent);
        $this->cookieManager->update($visitorCode, $legalConsent);
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
        VisitorCodeManager::validateVisitorCode($visitorCode);
        $featureFlag = $this->dataFile->getFeatureFlag($featureKey);
        [$variation, $rule] = $this->calculateVariationKeyForFeature($visitorCode, $featureFlag);
        $variationKey = $this->calculateVariationKey($variation, $rule, $featureFlag->defaultVariationKey);
        $experimentId = !is_null($rule) ? $rule->experimentId : null;
        $variationId = !is_null($variation) ? $variation->variationId : null;
        $this->saveVariation($visitorCode, $experimentId, $variationId, $rule !== null && $rule->isTargetedDelivery());
        $this->sendTrackingRequest($visitorCode);
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
        if ($rule != null && $rule->isExperiment()) {
            return Variation::VARIATION_OFF;
        }
        return $defaultVariationKey;
    }

    private function saveVariation(
        string $visitorCode,
        ?int $experimentId,
        ?int $variationId,
        bool $isTargetedDelivery
    ) {
        if (!is_null($experimentId) && !is_null($variationId)) {
            $this->visitorManager->getOrCreateVisitor($visitorCode)->assignVariation(
                $experimentId,
                $variationId,
                $isTargetedDelivery ?
                    AssignedVariation::RULE_TYPE_TARGETED_DELIVERY : AssignedVariation::RULE_TYPE_EXPERIMENTATION
            );
        }
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
        $dataFileShouldBeUpdated = $this->shouldDataFileBeUpdated();
        if (!isset($this->dataFile) || !$this->dataFile->isLoaded() || $dataFileShouldBeUpdated) {
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
            $this->dataFile->setLoaded();
        }
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
        $visitor = $this->visitorManager->getVisitor($visitorCode);
        switch ($type) {
            case CustomDatum::TYPE:
                return !is_null($visitor) ? $visitor->getCustomData() : null;

            case PageTitleCondition::TYPE:
                return !is_null($visitor) ? $visitor->getPageView() : null;

            case PageUrlCondition::TYPE:
                return !is_null($visitor) ? $visitor->getPageViewVisit() : null;

            case DeviceCondition::TYPE:
                return !is_null($visitor) ? $visitor->getDevice() : null;

            case BrowserCondition::TYPE:
                return !is_null($visitor) ? $visitor->getBrowser() : null;

            case ConversionCondition::TYPE:
                return !is_null($visitor) ? $visitor->getConversion() : null;

            case VisitorCodeCondition::TYPE:
                return $visitorCode;

            case TargetedExperiment::TYPE:
                return !is_null($visitor) ? $visitor->getAssignedVariations() : [];

            case ExclusiveExperiment::TYPE:
                return [$campaignId, !is_null($visitor) ? $visitor->getAssignedVariations() : []];

            case SdkLanguageCondition::TYPE:
                return new SdkInfo(SdkVersion::getName(), SdkVersion::getVersion());

            default:
                return null;
        }
    }

    private function applyNewConfiguration($dataFileJson)
    {
        $this->dataFile = new DataFile($dataFileJson, $this->clientConfig->getEnvironment());
        $settings = $this->dataFile->getSettings();
        $consentRequired = $settings->isConsentRequired()
            && !$this->dataFile->hasAnyTargetedDeliveryRule();
        $this->cookieManager->setConsentRequired($consentRequired);
        $this->networkManager->getUrlProvider()->applyDataApiDomain($settings->getDataApiDomain());
    }

    private function loadDataFileLocal()
    {
        $dataFileJsonLocal = json_decode(file_get_contents($this->configurationFilePath, true));
        if ($dataFileJsonLocal === null) {
            throw new DataFileInvalid("Local data file is invalid");
        }
        $this->applyNewConfiguration($dataFileJsonLocal);
    }

    private function updateConfiguration(int $timeout, bool $forceNetworkRequest = false)
    {
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
            error_log(
                "Saved data file will be used. The file needs to be updated, but an error occurred: " . $e->getMessage()
            );
            $this->loadDataFileLocal();
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

    private function calculateVariationKeyForFeature(string $visitorCode, FeatureFlag $featureFlag): array
    {
        // no rules -> return defaultVariationKey
        foreach ($featureFlag->rules as $rule) {
            // check if visitor is targeted for rule, else next rule
            if ($this->checkTargeting($visitorCode, $featureFlag->id, $rule)) {
                // uses for rule exposition
                $hashRule = HashDouble::obtain($visitorCode, $rule->id, $rule->respoolTime);
                // check main expostion for rule with hashRule
                if ($hashRule <= $rule->exposition) {
                    if ($rule->isTargetedDelivery() && count($rule->variationByExposition) > 0) {
                        return [$rule->variationByExposition[0], $rule];
                    }
                    // uses for variation's expositions
                    $hashVariation = HashDouble::obtain($visitorCode, $rule->experimentId, $rule->respoolTime);
                    // get variation key with new hashVariation
                    $variation = $this->calculateVariatonRuleHash($rule, $hashVariation);
                    // variation can be null for experiment rules only, for targeted rule will be always exist
                    if (!is_null($variation)) {
                        return [$variation, $rule];
                    }
                } elseif ($rule->isTargetedDelivery()) {
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

    private function sendTrackingRequest(string $visitorCode): void
    {
        $visitor = $this->visitorManager->getVisitor($visitorCode);
        $userAgent = !is_null($visitor) ? $visitor->getUserAgent() : null;
        $visitorData = iterator_to_array($this->getVisitorData($visitor));
        // We should send tracking data only if: content isn't required or legal consent is given from a visitor
        if (empty($visitorData) && $this->isConsentGiven($visitor)) {
            // Send activity tracking request if no experiment id or variation id
            $visitorData[] = new ActivityEvent();
        }
        if (!empty($visitorData)) {
            $this->networkManager->sendTrackingData(
                $visitorCode,
                $visitorData,
                $userAgent,
                $this->clientConfig->getDebugMode()
            );
            foreach ($visitorData as $data) {
                $data->markAsSent();
            }
        }
    }

    // The method returns visitor's data regarding consent type settings and visitor's legal consent
    private function getVisitorData(?Visitor $visitor): Generator
    {
        // If there is no visitor, then we just return empty collection despite the consent settings
        if (!is_null($visitor)) {
            if ($this->isConsentGiven($visitor)) {
                yield from $visitor->getUnsentData();
            } else {
                yield from $visitor->getUnsentConversion();
                yield from array_filter($visitor->getAssignedVariations(), function ($assignedVariation) {
                    return !$assignedVariation->isSent() &&
                        $assignedVariation->getRuleType() === AssignedVariation::RULE_TYPE_TARGETED_DELIVERY;
                });
            }
        }
    }

    // Retuns true if consent isn't required or visitor gave legal consent
    private function isConsentGiven(?Visitor $visitor): bool
    {
        $isConsentGiven = !$this->dataFile->getSettings()->isConsentRequired();
        return $isConsentGiven || (!is_null($visitor) && $visitor->getLegalConsent());
    }

    public function getVisitorWarehouseAudience(
        string $visitorCode,
        int $customDataIndex,
        ?string $warehouseKey = null,
        ?int $timeout = null
    ): ?CustomData {
        if (!isset($this->warehouseManager)) {
            $this->warehouseManager = new WarehouseManagerImpl($this->networkManager, $this->visitorManager);
        }
        return $this->warehouseManager->getVisitorWarehouseAudience(
            $visitorCode,
            $customDataIndex,
            $warehouseKey,
            $timeout
        );
    }
}
