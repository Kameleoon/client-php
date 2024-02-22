<?php

namespace Kameleoon\Network\AccessToken;

interface AccessTokenSource
{
    function getToken(?int $timeout = null): ?string;
    function discardToken(string $token);
}
