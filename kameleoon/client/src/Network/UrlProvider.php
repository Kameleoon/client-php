<?php

declare(strict_types=1);

namespace Kameleoon\Network;

use Exception;
use Kameleoon\Helpers\SdkVersion;

class UrlProvider
{
    const TRACKING_PATH = "/visit/events";
    const VISITOR_DATA_PATH = "/visit/visitor";
    const EXPERIMENTS_CONFIGURATIONS_PATH = "/visit/experimentsConfigurations";
    const GET_DATA_PATH = "/map/map";
    const POST_DATA_PATH = "/map/maps";
    const CONFIGURATION_API_URL_FORMAT = "https://%s.kameleoon.eu/sdk-config";
    const RT_CONFIGURATION_URL = "https://events.kameleoon.com:8110/sse";

    public const DEFAULT_DATA_API_URL = "https://data.kameleoon.io";
    public const TEST_DATA_API_URL = "https://data.kameleoon.net";

    private string $siteCode;
    private string $dataApiUrl;
    private string $postQueryBase;

    public function __construct(string $siteCode, string $dataApiUrl)
    {
        $this->siteCode = $siteCode;
        $this->dataApiUrl = $dataApiUrl;
        $this->postQueryBase = $this->makePostQueryBase();
    }

    public function getSiteCode(): string
    {
        return $this->siteCode;
    }

    public function getDataApiUrl(): string
    {
        return $this->dataApiUrl;
    }

    private function makePostQueryBase(): string
    {
        return (string)new QueryBuilder(
            new QueryParam(QueryParams::SDK_NAME, SdkVersion::getName()),
            new QueryParam(QueryParams::SDK_VERSION, SdkVersion::getVersion()),
            new QueryParam(QueryParams::SITE_CODE, $this->siteCode),
        );
    }

    public function makeTrackingUrl(string $visitorCode): string
    {
        $qp = new QueryParam(QueryParams::VISITOR_CODE, $visitorCode);
        return sprintf("%s%s?%s&%s", $this->dataApiUrl, self::TRACKING_PATH, $this->postQueryBase, $qp);
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
                new QueryParam(QueryParams::DEBUG, "true"),
                new QueryParam(QueryParams::URL, rawurlencode($currentUrl)),
                new QueryParam(QueryParams::IP, rawurlencode($ip)),
                new QueryParam(QueryParams::UA, rawurlencode($http_user_agent)),
            );
        } catch (Exception $_) {
            return null;
        }
    }

    public function makeVisitorDataGetUrl(string $visitorCode): string
    {
        $qb = new QueryBuilder(
            new QueryParam(QueryParams::SITE_CODE, $this->siteCode),
            new QueryParam(QueryParams::VISITOR_CODE, $visitorCode),
            new QueryParam(QueryParams::CURRENT_VISIT, "true"),
            new QueryParam(QueryParams::MAX_NUMBER_PREVIOUS_VISITS, "1"),
            new QueryParam(QueryParams::CUSTOM_DATA, "true"),
            new QueryParam(QueryParams::VERSION, "0"),
        );
        return sprintf("%s%s?%s", $this->dataApiUrl, self::VISITOR_DATA_PATH, $qb);
    }

    public function makeApiDataGetRequestUrl(string $key): string
    {
        $qb = new QueryBuilder(
            new QueryParam(QueryParams::SITE_CODE, $this->siteCode),
            new QueryParam(QueryParams::KEY, $key),
        );
        return sprintf("%s%s?%s", $this->dataApiUrl, self::GET_DATA_PATH, $qb);
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
}
