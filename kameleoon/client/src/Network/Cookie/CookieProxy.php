<?php

namespace Kameleoon\Network\Cookie;

use Kameleoon\Logging\KameleoonLogger;

class CookieProxy implements ICookieProxy
{
    public function setCookieArray(string $name, $value = '', array $options = []): bool
    {
        KameleoonLogger::debug(
            "CALL: CookieProxy->setCookieArray(name: '%s', value: '%s', options: %s)",
            $name,
            $value,
            $options
        );
        $cookieSet = setcookie($name, $value, $options);
        KameleoonLogger::debug(
            "RETURN: CookieProxy->setCookieArray(name: '%s', value: '%s', options: %s) -> (cookieSet: %s)",
            $name,
            $value,
            $options,
            $cookieSet
        );
        return $cookieSet;
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
        KameleoonLogger::debug(
            "CALL: CookieProxy->setCookie( name: '%s', value: '%s', expires: %s, path: '%s', domain: '%s'," .
                " secure: %s, httponly: %s]",
            $name,
            $value,
            $expires_or_options,
            $path,
            $domain,
            $secure,
            $httponly
        );
        $cookieSet = setcookie($name, $value, $expires_or_options, $path, $domain, $secure, $httponly);
        KameleoonLogger::debug(
            "RETURN: CookieProxy->setCookie( name: '%s', value: '%s', expires: %s, path: '%s', domain: '%s'," .
                " secure: %s, httponly: %s] -> (cookieSet: %s)",
            $name,
            $value,
            $expires_or_options,
            $path,
            $domain,
            $secure,
            $httponly,
            $cookieSet
        );
        return $cookieSet;
    }

    public function getCookie(string $name): ?string
    {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        }
        return null;
    }
}
