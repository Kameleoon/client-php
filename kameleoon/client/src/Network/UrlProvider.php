<?php

declare(strict_types=1);

namespace Kameleoon\Network;

use Exception;
use Kameleoon\Helpers\SdkVersion;
use Kameleoon\Types\RemoteVisitorDataFilter;

class UrlProvider
{
    const CONFIGURATION_API_URL_FORMAT = "https://sdk-config.kameleoon.eu/%s";
    const RT_CONFIGURATION_URL = "https://events.kameleoon.com:8110/sse";

    public const DEFAULT_DATA_API_DOMAIN = "data.kameleoon.io";
    public const TEST_DATA_API_DOMAIN = "data.kameleoon.net";
    const TRACKING_PATH = "/visit/events";
    const VISITOR_DATA_PATH = "/visit/visitor";
    const GET_DATA_PATH = "/map/map";
    const POST_DATA_PATH = "/map/maps";

    const AUTOMATION_API_URL = "https://api.kameleoon.com";
    const TEST_AUTOMATION_API_URL = "https://api.kameleoon.com";
    public const ACCESS_TOKEN_PATH = "/oauth/token";

    private string $siteCode;
    private string $dataApiDomain;
    private string $postQueryBase;

    public function __construct(string $siteCode, string $dataApiDomain)
    {
        $this->siteCode = $siteCode;
        $this->dataApiDomain = $dataApiDomain;
        $this->postQueryBase = $this->makeTrackingQueryBase();
    }

    public function getSiteCode(): string
    {
        return $this->siteCode;
    }

    public function getDataApiDomain(): string
    {
        return $this->dataApiDomain;
    }

    public function applyDataApiDomain(?string $dataApiDomain): void
    {
        if ($dataApiDomain !== null) {
            $this->dataApiDomain = $dataApiDomain;
        }
    }

    private function makeTrackingQueryBase(): string
    {
        return (string)new QueryBuilder(
            new QueryParam(QueryParams::SDK_NAME, SdkVersion::getName()),
            new QueryParam(QueryParams::SDK_VERSION, SdkVersion::getVersion()),
            new QueryParam(QueryParams::SITE_CODE, $this->siteCode),
            new QueryParam(QueryParams::BODY_UA, "true", false),
        );
    }

    public function makeTrackingUrl(): string
    {
        return sprintf("https://%s%s?%s", $this->dataApiDomain, self::TRACKING_PATH, $this->postQueryBase);
    }

    public function makeExperimentRegisterDebugParams(): ?string
    {
        try {
            $currentUrl = "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            if ($ip === "") {
                $ip = "";
            }
            $http_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? "";
            return "&" . new QueryBuilder(
                new QueryParam(QueryParams::DEBUG, "true", false),
                new QueryParam(QueryParams::URL, rawurlencode($currentUrl)), # //~ Why are these values encoded twice?
                new QueryParam(QueryParams::IP, rawurlencode($ip)),
                new QueryParam(QueryParams::UA, rawurlencode($http_user_agent)),
            );
        } catch (Exception $_) {
            return null;
        }
    }

    public function makeVisitorDataGetUrl(
        string $visitorCode,
        RemoteVisitorDataFilter $filter,
        bool $isUniqueIdentifier
    ): string {
        $qb = new QueryBuilder(
            new QueryParam(QueryParams::SITE_CODE, $this->siteCode),
            new QueryParam($isUniqueIdentifier ? QueryParams::MAPPING_VALUE : QueryParams::VISITOR_CODE, $visitorCode),
            new QueryParam(QueryParams::MAX_NUMBER_PREVIOUS_VISITS, (string)$filter->previousVisitAmount),
            new QueryParam(QueryParams::VERSION, "0", false),
        );
        self::addFlagParamIfRequired($qb, QueryParams::KCS, $filter->kcs);
        self::addFlagParamIfRequired($qb, QueryParams::CURRENT_VISIT, $filter->currentVisit);
        self::addFlagParamIfRequired($qb, QueryParams::CUSTOM_DATA, $filter->customData);
        self::addFlagParamIfRequired($qb, QueryParams::CONVERSION, $filter->conversion);
        self::addFlagParamIfRequired($qb, QueryParams::GEOLOCATION, $filter->geolocation);
        self::addFlagParamIfRequired($qb, QueryParams::EXPERIMENT, $filter->experiments);
        self::addFlagParamIfRequired($qb, QueryParams::PAGE, $filter->pageViews);
        self::addFlagParamIfRequired(
            $qb,
            QueryParams::STATIC_DATA,
            ($filter->device || $filter->browser || $filter->operatingSystem)
        );
        return sprintf("https://%s%s?%s", $this->dataApiDomain, self::VISITOR_DATA_PATH, $qb);
    }
    private static function addFlagParamIfRequired(QueryBuilder $qb, string $paramName, bool $state): void
    {
        if ($state) {
            $qb->append(new QueryParam($paramName, "true", false));
        }
    }

    public function makeApiDataGetRequestUrl(string $key): string
    {
        $qb = new QueryBuilder(
            new QueryParam(QueryParams::SITE_CODE, $this->siteCode),
            new QueryParam(QueryParams::KEY, $key),
        );
        return sprintf("https://%s%s?%s", $this->dataApiDomain, self::GET_DATA_PATH, $qb);
    }

    public function makeConfigurationUrl(?string $environment = null): string
    {
        $qb = new QueryBuilder();
        if ($environment !== null) {
            $qb->append(new QueryParam(QueryParams::ENVIRONMENT, $environment));
        }
        $url = sprintf(self::CONFIGURATION_API_URL_FORMAT, $this->siteCode);
        $query = (string)$qb;
        if (!empty($query)) {
            $url .= "?$query";
        }
        return $url;
    }

    public function makeRealTimeUrl(): string
    {
        $qp = new QueryParam(QueryParams::SITE_CODE, $this->siteCode);
        return sprintf("%s?%s", self::RT_CONFIGURATION_URL, $qp);
    }

    public function makeAccessTokenUrl(): string
    {
        return sprintf("%s%s", UrlProvider::AUTOMATION_API_URL, UrlProvider::ACCESS_TOKEN_PATH);
    }
}
