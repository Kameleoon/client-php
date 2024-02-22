<?php

namespace Kameleoon\Network\Cookie;

use Kameleoon\CookieOptions;
use Kameleoon\Exception\ConfigException;
use Kameleoon\Helpers\RandomString;
use Kameleoon\Helpers\VisitorCodeManager;

class CookieManagerImpl implements CookieManager
{
    const KAMELEOON_VISITOR_CODE = "kameleoonVisitorCode";
    const COOKIE_HEADER_RESPONSE = "Set-Cookie";
    const COOKIE_HEADER_REQUEST = "Cookie";
    const MAX_AGE_COOKIE_SECONDS = 32_832_000;
    const REMOVE_COOKIE_SECONDS = -3600;

    private CookieOptions $cookieOptions;
    private bool $isConsentRequired = false;
    private ICookieProxy $cookieProxy;

    public function __construct(CookieOptions $cookieOptions, ?ICookieProxy $cookieProxy = null)
    {
        $this->cookieOptions = $cookieOptions;
        $this->cookieProxy = $cookieProxy ?? new CookieProxy();
    }

    public function setConsentRequired(bool $consentRequired): void
    {
        $this->isConsentRequired = $consentRequired;
    }

    public function getOrAdd(?string $visitorCode = null)
    {
        $visitorCode = $this->getFromCookie() ?? $visitorCode;

        if ($visitorCode === null) {
            $visitorCode = VisitorCodeManager::generateVisitorCode();
        }

        if ($this->cookieOptions->getTopLevelDomain() === null) {
            throw new ConfigException('Domain is required');
        }

        if (!$this->isConsentRequired) {
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
        if (version_compare(phpversion(), '7.3', '<')) {
            $this->setCookiePriorPHP73($visitorCode);
        } else {
            $this->setCookie($visitorCode);
        }
    }

    private function remove(): void
    {
        if ($this->isConsentRequired) {
            // time() - 3600 to mark cookie as expired
            $this->cookieProxy->setCookie(self::KAMELEOON_VISITOR_CODE, '', time() + self::REMOVE_COOKIE_SECONDS, '/');
        }
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

class CookieProxy implements ICookieProxy
{
    public function setCookieArray(string $name, $value = '', array $options = []): bool
    {
        return setcookie($name, $value, $options);
    }

    public function setCookie(
        string $name,
        $value = "",
        $expires_or_options = 0,
        $path = "",
        $domain = "",
        $secure = false,
        $httponly = false
    ): bool {
        return setcookie($name, $value, $expires_or_options, $path, $domain, $secure, $httponly);
    }

    public function getCookie(string $name): ?string
    {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        }
        return null;
    }
}
