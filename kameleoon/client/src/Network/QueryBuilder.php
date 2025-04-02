<?php

declare(strict_types=1);

namespace Kameleoon\Network;

use Kameleoon\Helpers\URLEncoding;

class QueryBuilder
{
    private string $query;

    public function __construct(QueryParam ...$params)
    {
        $this->query = "";
        foreach ($params as $param) {
            $this->append($param);
        }
    }

    public function append(QueryParam $param): void
    {
        $strParam = (string)$param;
        if (!empty($this->query) && !empty($strParam)) {
            $this->query .= "&";
        }
        $this->query .= $strParam;
    }

    public function __toString(): string
    {
        return $this->query;
    }
}

class QueryParam
{
    private string $name;
    private ?string $value;
    private bool $encodingRequired;

    public function __construct(string $name, ?string $value, bool $encodingRequired = true)
    {
        $this->name = $name;
        $this->value = $value;
        $this->encodingRequired = $encodingRequired;
    }

    public function __toString(): string
    {
        if ($this->value === null) {
            return "";
        }
        $encodedValue = $this->encodingRequired ? URLEncoding::encodeURIComponent($this->value) : $this->value;
        return sprintf("%s=%s", $this->name, $encodedValue);
    }
}

class QueryParams
{
    public const BODY_UA = "bodyUa";
    public const BROWSER_INDEX = "browserIndex";
    public const BROWSER_VERSION = "browserVersion";
    public const CBS = "cbs";
    public const CITY = "city";
    public const CONVERSION = "conversion";
    public const COUNTRY = "country";
    public const CURRENT_VISIT = "currentVisit";
    public const CUSTOM_DATA = "customData";
    public const DEVICE_TYPE = "deviceType";
    public const ENVIRONMENT = "environment";
    public const EVENT_TYPE = "eventType";
    public const EXPERIMENT = "experiment";
    public const EXPERIMENT_ID = "id";
    public const GEOLOCATION = "geolocation";
    public const GOAL_ID = "goalId";
    public const HREF = "href";
    public const INDEX = "index";
    public const KCS = "kcs";
    public const KEY = "key";
    public const LATITUDE = "latitude";
    public const LONGITUDE = "longitude";
    public const MAPPING_IDENTIFIER = "mappingIdentifier";
    public const MAPPING_VALUE = "mappingValue";
    public const MAX_NUMBER_PREVIOUS_VISITS = "maxNumberPreviousVisits";
    public const METADATA = "metadata";
    public const NEGATIVE = "negative";
    public const NONCE = "nonce";
    public const OS = "os";
    public const OS_INDEX = "osIndex";
    public const OVERWRITE = "overwrite";
    public const PAGE = "page";
    public const PERSONALIZATION = "personalization";
    public const POSTAL_CODE = "postalCode";
    public const REFERRERS_INDICES = "referrersIndices";
    public const REGION = "region";
    public const REVENUE = "revenue";
    public const SDK_NAME = "sdkName";
    public const SDK_VERSION = "sdkVersion";
    public const SITE_CODE = "siteCode";
    public const STATIC_DATA = "staticData";
    public const TITLE = "title";
    public const USER_AGENT = "userAgent";
    public const VALUES_COUNT_MAP = "valuesCountMap";
    public const VARIATION_ID = "variationId";
    public const VERSION = "version";
    public const VISITOR_CODE = "visitorCode";

    public const GRANT_TYPE = "grant_type";
    public const CLIENT_ID = "client_id";
    public const CLIENT_SECRET = "client_secret";

    public const DEBUG = "debug";
    public const URL = "url";
    public const IP = "ip";
    public const UA = "ua";
}
