<?php

declare(strict_types=1);

namespace Kameleoon\Helpers;

use Kameleoon\Logging\KameleoonLogger;

class Domain
{
    private const HTTP = 'http://';
    private const HTTPS = 'https://';
    private const REGEX_DOMAIN = '/^(\.?(([a-zA-Z\d][a-zA-Z\d-]*[a-zA-Z\d])|[a-zA-Z\d]))'
    . '(\.(([a-zA-Z\d][a-zA-Z\d-]*[a-zA-Z\d])|[a-zA-Z\d])){1,126}$/';
    private const LOCALHOST = 'localhost';

    public static function validateTopLevelDomain(?string $topLevelDomain): ?string
    {
        if ($topLevelDomain == null) {
            return null;
        }

        $topLevelDomain = Domain::checkAndTrimProtocol(strtolower($topLevelDomain));

        if (!preg_match(self::REGEX_DOMAIN, $topLevelDomain) && $topLevelDomain !== self::LOCALHOST) {
            KameleoonLogger::error(
                "The top-level domain '%s' is invalid. The value has been set as provided, but it does not meet " .
                 "the required format for proper SDK functionality. Please check the domain for correctness.",
                $topLevelDomain);
            return $topLevelDomain;
        }

        return $topLevelDomain;
    }

    public static function validateNetworkDomain(?string $networkDomain): ?string
    {
        if ($networkDomain == null) {
            return null;
        }

        $networkDomain = Domain::checkAndTrimProtocol(strtolower($networkDomain));

        // remove first and last dot
        $networkDomain = trim($networkDomain, ".");

        if (!preg_match(self::REGEX_DOMAIN, $networkDomain) && $networkDomain !== self::LOCALHOST) {
            KameleoonLogger::error("The network domain '%s' is invalid.", $networkDomain);
            return null;
        }

        return $networkDomain;
    }

    private static function checkAndTrimProtocol(string $domain): string
    {
        $protocols = [self::HTTP, self::HTTPS];
        foreach ($protocols as $protocol) {
            if (strpos($domain, $protocol) === 0) {
                $domain = substr($domain, strlen($protocol));
                KameleoonLogger::warning("The domain contains '%s'. Domain after protocol trimming: '%s'",
                    $protocol, $domain);
                break;
            }
        }
        return $domain;
    }
}
