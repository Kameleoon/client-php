<?php

namespace Kameleoon\Network\Cookie;

use Kameleoon\CookieOptions;
use Kameleoon\Exception\ConfigException;
use Kameleoon\Helpers\VisitorCodeManager;
use Kameleoon\Logging\KameleoonLogger;
use Kameleoon\Managers\Data\DataManager;

class CookieManagerImpl implements CookieManager
{
    const KAMELEOON_VISITOR_CODE = "kameleoonVisitorCode";
    const COOKIE_HEADER_RESPONSE = "Set-Cookie";
    const COOKIE_HEADER_REQUEST = "Cookie";
    const MAX_AGE_COOKIE_SECONDS = 32_832_000;
    const REMOVE_COOKIE_SECONDS = -3600;

    private CookieOptions $cookieOptions;
    private ICookieProxy $cookieProxy;
    private DataManager $dataManager;

    public function __construct(
        DataManager $dataManager, CookieOptions $cookieOptions, ?ICookieProxy $cookieProxy = null)
    {
        KameleoonLogger::debug("CALL: new CookieManager(dataManager, cookieOptions, cookieProxy)");
        $this->dataManager = $dataManager;
        $this->cookieOptions = $cookieOptions;
        $this->cookieProxy = $cookieProxy ?? new CookieProxy();
        KameleoonLogger::debug("RETURN: new CookieManager(dataManager, cookieOptions, cookieProxy)");
    }

    public function getOrAdd(?string $visitorCode = null)
    {
        KameleoonLogger::debug("CALL: CookieManager->getOrAdd(visitorCode: '%s')", $visitorCode);
        $visitorCode = $this->getFromCookie() ?? $visitorCode;

        if ($visitorCode === null) {
            $visitorCode = VisitorCodeManager::generateVisitorCode();
        }

        if ($this->cookieOptions->getTopLevelDomain() === null) {
            throw new ConfigException('Domain is required');
        }

        if (!$this->dataManager->doesVisitorCodeManagementRequireConsent()) {
            $this->add($visitorCode);
        }

        KameleoonLogger::debug(
            "RETURN: CookieManager->getOrAdd(visitorCode: '%s') -> (visitorCode: '%s')",
            $visitorCode,
            $visitorCode
        );
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

    private function getFromCookie(): ?string
    {
        $visitorCode = null;
        if ($visitorCode = $this->cookieProxy->getCookie(self::KAMELEOON_VISITOR_CODE)) {
            // _js_ - support to 22.08.2024
            if (strpos($visitorCode, "_js_") !== false) {
                $visitorCode = substr($visitorCode, 4);
            }
        }
        return $visitorCode;
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
