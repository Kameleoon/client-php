<?php

namespace Kameleoon\Network\Cookie;

interface ICookieProxy
{
    function setCookieArray(string $name, $value = '', array $options = []): bool;
    function setCookie(
        string $name,
        $value = "",
        $expires_or_options = 0,
        $path = "",
        $domain = "",
        $secure = false,
        $httponly = false
    ): bool;
    function getCookie(string $name);
}
