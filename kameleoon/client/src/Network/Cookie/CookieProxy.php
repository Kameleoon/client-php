<?php

namespace Kameleoon\Network\Cookie;

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
