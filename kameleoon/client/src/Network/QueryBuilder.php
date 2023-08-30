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

    public function __construct(string $name, ?string $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function __toString(): string
    {
        if ($this->value === null) {
            return "";
        }
        $encodedValue = URLEncoding::encodeURIComponent($this->value);
        return sprintf("%s=%s", $this->name, $encodedValue);
    }
}

class QueryParams
{
    public const BROWSER_INDEX = "browserIndex";
    public const BROWSER_VERSION = "browserVersion";
    public const CURRENT_VISIT = "currentVisit";
    public const CUSTOM_DATA = "customData";
    public const DEVICE_TYPE = "deviceType";
    public const ENVIRONMENT = "environment";
    public const EVENT_TYPE = "eventType";
    public const EXPERIMENT_ID = "id";
    public const GOAL_ID = "goalId";
    public const HREF = "href";
    public const INDEX = "index";
    public const KEY = "key";
    public const MAX_NUMBER_PREVIOUS_VISITS = "maxNumberPreviousVisits";
    public const NEGATIVE = "negative";
    public const NONCE = "nonce";
    public const OVERWRITE = "overwrite";
    public const REFERRERS_INDICES = "referrersIndices";
    public const REVENUE = "revenue";
    public const SDK_NAME = "sdkName";
    public const SDK_VERSION = "sdkVersion";
    public const SITE_CODE = "siteCode";
    public const TITLE = "title";
    public const VALUES_COUNT_MAP = "valuesCountMap";
    public const VARIATION_ID = "variationId";
    public const VERSION = "version";
    public const VISITOR_CODE = "visitorCode";

    public const DEBUG = "debug";
    public const URL = "url";
    public const IP = "ip";
    public const UA = "ua";
}
