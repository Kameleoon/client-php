<?php

declare(strict_types=1);

namespace Kameleoon;

use Kameleoon\Exception\ConfigCredentialsInvalid;

class KameleoonClientConfig
{
    const DEFAULT_KAMELEOON_WORK_DIR = "/tmp/kameleoon/php-client/";
    const DEFAULT_TIMEOUT_MILLISECONDS = 10_000;
    const SECONDS_IN_MINUTE = 60;
    const DEFAULT_REFRESH_INTERVAL_MINUTES = 60;

    private string $clientId;
    private string $clientSecret;
    private string $kameleoonWorkDir;
    private int $refreshIntervalSecond;
    private int $defaultTimeoutMillisecond;
    private bool $debugMode;
    private CookieOptions $cookieOptions;
    private ?string $environment;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $kameleoonWorkDir = self::DEFAULT_KAMELEOON_WORK_DIR,
        int $refreshIntervalMinute = self::DEFAULT_REFRESH_INTERVAL_MINUTES,
        int $defaultTimeoutMillisecond = self::DEFAULT_TIMEOUT_MILLISECONDS,
        bool $debugMode = false,
        ?CookieOptions $cookieOptions = null,
        ?string $environment = null
    ) {
        if (empty($clientId)) {
            throw new ConfigCredentialsInvalid("Client ID is not specified or empty");
        }
        $this->clientId = $clientId;
        if (empty($clientSecret)) {
            throw new ConfigCredentialsInvalid("Client secret is not specified or empty");
        }
        $this->clientSecret = $clientSecret;
        $this->kameleoonWorkDir = $kameleoonWorkDir;
        if ($refreshIntervalMinute <= 0) {
            error_log("Kameleoon SDK: Refresh interval must have positive value. Default refresh interval ("
                . self::DEFAULT_REFRESH_INTERVAL_MINUTES . " minutes) is applied");
            $refreshIntervalMinute = self::DEFAULT_REFRESH_INTERVAL_MINUTES;
        }
        $this->refreshIntervalSecond = $refreshIntervalMinute * self::SECONDS_IN_MINUTE;
        if ($defaultTimeoutMillisecond <= 0) {
            error_log("Kameleoon SDK: Default timeout must have positive value. Default timeout ("
                . self::DEFAULT_TIMEOUT_MILLISECONDS . " ms) is applied");
            $defaultTimeoutMillisecond = self::DEFAULT_TIMEOUT_MILLISECONDS;
        }
        $this->defaultTimeoutMillisecond = $defaultTimeoutMillisecond;
        $this->debugMode = $debugMode;
        $this->cookieOptions = $cookieOptions ?? new CookieOptions();
        if (empty($this->cookieOptions->getTopLevelDomain())) {
            error_log("Kameleoon SDK: Setting parameter 'topLevelDomain' (top_level_domain) is strictly "
                . "recommended otherwise you may have problems when using subdomains.");
        }
        $this->environment = $environment;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function getKameleoonWorkDir(): string
    {
        return $this->kameleoonWorkDir;
    }

    public function getRefreshIntervalSecond(): int
    {
        return $this->refreshIntervalSecond;
    }

    public function getDefaultTimeoutMillisecond(): int
    {
        return $this->defaultTimeoutMillisecond;
    }

    public function getDebugMode(): bool
    {
        return $this->debugMode;
    }

    public function getCookieOptions(): CookieOptions
    {
        return $this->cookieOptions;
    }

    public function getEnvironment(): ?string
    {
        return $this->environment;
    }

    public static function readFromFile(string $filePath)
    {
        $kameleoonConfigJson = array();
        if (file_exists($filePath) && is_file($filePath)) {
            $config = file_get_contents($filePath, true);
            $kameleoonConfigJson = json_decode($config, true);
        }
        return new self(
            $kameleoonConfigJson["client_id"] ?? "",
            $kameleoonConfigJson["client_secret"] ?? "",
            $kameleoonConfigJson["kameleoon_work_dir"] ?? self::DEFAULT_KAMELEOON_WORK_DIR,
            $kameleoonConfigJson["refresh_interval_minute"] ?? self::DEFAULT_REFRESH_INTERVAL_MINUTES,
            $kameleoonConfigJson["default_timeout_millisecond"] ?? self::DEFAULT_TIMEOUT_MILLISECONDS,
            $kameleoonConfigJson["debug_mode"] ?? false,
            CookieOptions::readFromArray($kameleoonConfigJson["cookie_options"] ?? null),
            $kameleoonConfigJson["environment"] ?? null
        );
    }

    public static function createCookieOptions(
        ?string $topLevelDomain = null,
        bool $secure = false,
        bool $httpOnly = false,
        string $sameSite = CookieOptions::DEFAULT_SAMESITE
    ) {
        return new CookieOptions($topLevelDomain, $secure, $httpOnly, $sameSite);
    }
}

class CookieOptions
{
    const DEFAULT_SAMESITE = "Lax";
    // Need to make it public readonly after migration to PHP 8.1+
    private bool $secure;
    private bool $httpOnly;
    private string $sameSite;
    private ?string $topLevelDomain;

    public function __construct(
        ?string $topLevelDomain = null,
        bool $secure = false,
        bool $httpOnly = false,
        string $sameSite = self::DEFAULT_SAMESITE
    ) {
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;
        $this->sameSite = $sameSite;
        $this->topLevelDomain = $topLevelDomain;
    }

    public function getSecure(): bool
    {
        return $this->secure;
    }

    public function getHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    public function getSameSite(): string
    {
        return $this->sameSite;
    }

    public function getTopLevelDomain(): ?string
    {
        return $this->topLevelDomain;
    }

    public static function readFromArray(?array $cookieOption)
    {
        if (is_null($cookieOption)) {
            return new self();
        }

        return new self(
            $cookieOption["domain"] ?? null,
            $cookieOption["secure"] ?? false,
            $cookieOption["http_only"] ?? false,
            $cookieOption["samesite"] ?? self::DEFAULT_SAMESITE,
        );
    }
}
