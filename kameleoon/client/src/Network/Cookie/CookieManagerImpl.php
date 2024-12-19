<?php

namespace Kameleoon\Network\Cookie;

use Exception;
use Kameleoon\CookieOptions;
use Kameleoon\Configuration\DataFile;
use Kameleoon\Data\Manager\ForcedFeatureVariation;
use Kameleoon\Data\Manager\VisitorManager;
use Kameleoon\Exception\ConfigException;
use Kameleoon\Helpers\VisitorCodeManager;
use Kameleoon\Logging\KameleoonLogger;
use Kameleoon\Managers\Data\DataManager;

class CookieManagerImpl implements CookieManager
{
    const KAMELEOON_VISITOR_CODE = "kameleoonVisitorCode";
    const KAMELEOON_SIMULATION_FF_DATA = "kameleoonSimulationFFData";
    const COOKIE_HEADER_RESPONSE = "Set-Cookie";
    const COOKIE_HEADER_REQUEST = "Cookie";
    const MAX_AGE_COOKIE_SECONDS = 32_832_000;
    const REMOVE_COOKIE_SECONDS = -3600;

    private CookieOptions $cookieOptions;
    private VisitorManager $visitorManager;
    private ICookieProxy $cookieProxy;
    private DataManager $dataManager;

    public function __construct(
        DataManager $dataManager, VisitorManager $visitorManager,
        CookieOptions $cookieOptions, ?ICookieProxy $cookieProxy = null)
    {
        KameleoonLogger::debug("CALL: new CookieManager(dataManager, visitorManager, cookieOptions, cookieProxy)");
        $this->dataManager = $dataManager;
        $this->visitorManager = $visitorManager;
        $this->cookieOptions = $cookieOptions;
        $this->cookieProxy = $cookieProxy ?? new CookieProxy();
        KameleoonLogger::debug("RETURN: new CookieManager(dataManager, visitorManager, cookieOptions, cookieProxy)");
    }

    public function getOrAdd(?string $visitorCode = null)
    {
        KameleoonLogger::debug("CALL: CookieManager->getOrAdd(visitorCode: '%s')", $visitorCode);
        $visitorCode = $this->getOrAddVisitorCode($visitorCode);
        $this->processSimulatedVariations($visitorCode);
        KameleoonLogger::debug(
            "RETURN: CookieManager->getOrAdd(visitorCode: '%s') -> (visitorCode: '%s')",
            $visitorCode,
            $visitorCode
        );
        return $visitorCode;
    }

    private function getOrAddVisitorCode(?string $visitorCode = null)
    {
        $visitorCode = $this->cookieProxy->getCookie(self::KAMELEOON_VISITOR_CODE) ?? $visitorCode;
        if ($visitorCode === null) {
            $visitorCode = VisitorCodeManager::generateVisitorCode();
        }
        if ($this->cookieOptions->getTopLevelDomain() === null) {
            throw new ConfigException('Domain is required');
        }
        if (!$this->dataManager->doesVisitorCodeManagementRequireConsent()) {
            $this->add($visitorCode);
        }
        return $visitorCode;
    }

    public function update(string $visitorCode, bool $legalConsent)
    {
        if ($legalConsent) {
            $this->add($visitorCode);
        } else {
            $this->remove();
        }
    }

    private function add(string $visitorCode): void
    {
        KameleoonLogger::debug("CALL: CookieManager->add(visitorCode: '%s')", $visitorCode);
        if (version_compare(phpversion(), '7.3', '<')) {
            $this->setCookiePriorPHP73($visitorCode);
        } else {
            $this->setCookie($visitorCode);
        }
        KameleoonLogger::debug("RETURN: CookieManager->add(visitorCode: '%s')", $visitorCode);
    }

    private function remove(): void
    {
        KameleoonLogger::debug("CALL: CookieManager->remove()");
        if ($this->dataManager->doesVisitorCodeManagementRequireConsent()) {
            // time() - 3600 to mark cookie as expired
            $this->cookieProxy->setCookie(self::KAMELEOON_VISITOR_CODE, '', time() + self::REMOVE_COOKIE_SECONDS, '/');
        }
        KameleoonLogger::debug("RETURN: CookieManager->remove()");
    }

    private function processSimulatedVariations(string $visitorCode): void
    {
        try {
            $raw = $this->cookieProxy->getCookie(self::KAMELEOON_SIMULATION_FF_DATA);
            if ($raw === null) return;
            $variations = $this->parseSimulatedVariations($raw);
            $visitor = $this->visitorManager->getOrCreateVisitor($visitorCode);
            $visitor->updateSimulatedVariations($variations);
        } catch (Exception $ex) {
            KameleoonLogger::error("Failed to process simulated variations cookie: %s", $ex);
        }
    }

    private function parseSimulatedVariations(string $raw): array
    {
        $dataFile = $this->dataManager->getDataFile();
        $jobj = json_decode($raw);
        if ($jobj === null) {
            KameleoonLogger::error("Failed to parse simulated variations cookie: %s", $raw);
            return [];
        }
        if (!is_object($jobj)) {
            self::logMalformedSimulatedVariationsCookie($raw, "object expected");
            return [];
        }
        $variations = [];
        foreach ($jobj as $featureKey => $value) {
            if (!is_string($featureKey)) {
                self::logMalformedSimulatedVariationsCookie($raw, "key must be string");
                continue;
            }
            $experimentId = $value->expId ?? null;
            if (!is_int($experimentId) || ($experimentId < 0)) {
                self::logMalformedSimulatedVariationsCookie($raw, "'expId' must be non-negative integer");
                continue;
            }
            $variationId = null;
            if ($experimentId > 0) {
                $variationId = $value->varId ?? null;
                if (!is_int($variationId) || ($experimentId < 0)) {
                    self::logMalformedSimulatedVariationsCookie($raw, "'varId' must be non-negative integer");
                    continue;
                }
            }
            $simulatedVariation = self::simulatedVariationFromDataFile(
                $dataFile, $featureKey, $experimentId, $variationId
            );
            if ($simulatedVariation !== null) {
                $variations[] = $simulatedVariation;
            }
        }
        return $variations;
    }

    private static function logMalformedSimulatedVariationsCookie(string $raw, string $info): void
    {
        KameleoonLogger::error("Malformed simulated variations cookie '%s': %s", $raw, $info);
    }

    private static function simulatedVariationFromDataFile(
        DataFile $dataFile, string $featureKey, int $experimentId, ?int $variationId): ?ForcedFeatureVariation
    {
        $featureFlag = $dataFile->getFeatureFlags()[$featureKey] ?? null;
        if ($featureFlag === null) {
            KameleoonLogger::error("Simulated feature flag '%s' is not found", $featureKey);
            return null;
        }
        if ($experimentId == 0) {
            return new ForcedFeatureVariation($featureKey, null, null, true);
        }
        foreach ($featureFlag->rules as $rule) {
            if ($rule->experimentId != $experimentId) continue;
            foreach ($rule->variationByExposition as $varByExp) {
                if ($varByExp->variationId == $variationId) {
                    return new ForcedFeatureVariation($featureKey, $rule, $varByExp, true);
                }
            }
            KameleoonLogger::error("Simulated variation %s is not found", $variationId);
            return null;
        }
        KameleoonLogger::error("Simulated experiment %s is not found", $experimentId);
        return null;
    }

    private function setCookiePriorPHP73(string $visitorCode): void
    {
        $this->cookieProxy->setCookie(
            self::KAMELEOON_VISITOR_CODE,
            $visitorCode,
            time() + self::MAX_AGE_COOKIE_SECONDS,
            "/",
            $this->cookieOptions->getTopLevelDomain(),
            $this->cookieOptions->getSecure(),
            $this->cookieOptions->getHttpOnly()
        );
    }

    private function setCookie(string $visitorCode): void
    {
        $cookieOptions = array(
            "expires" => time() + self::MAX_AGE_COOKIE_SECONDS,
            "path" => '/',
            "domain" => $this->cookieOptions->getTopLevelDomain(),
            "secure" => $this->cookieOptions->getSecure(),
            "httponly" => $this->cookieOptions->getHttpOnly(),
            "samesite" => $this->cookieOptions->getSameSite()
        );
        $this->cookieProxy->setCookieArray(self::KAMELEOON_VISITOR_CODE, $visitorCode, $cookieOptions);
    }
}
