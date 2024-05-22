<?php

declare(strict_types=1);

namespace Kameleoon\Data;

use Kameleoon\Network\QueryBuilder;
use Kameleoon\Network\QueryParam;
use Kameleoon\Network\QueryParams;
use Kameleoon\Network\Sendable;

class OperatingSystem extends Sendable implements Data
{
    public const EVENT_TYPE = "staticData";

    public const WINDOWS = 0;
    public const MAC = 1;
    public const IOS = 2;
    public const LINUX = 3;
    public const ANDROID = 4;
    public const WINDOWS_PHONE = 5;

    public static $typeNames = ["WINDOWS", "MAC", "IOS", "LINUX", "ANDROID", "WINDOWS_PHONE"];
    public static $typeIndices = array(
        "WINDOWS" => self::WINDOWS,
        "MAC" => self::MAC,
        "IOS" => self::IOS,
        "LINUX" => self::LINUX,
        "ANDROID" => self::ANDROID,
        "WINDOWS_PHONE" => self::WINDOWS_PHONE,
    );

    private int $type;

    public function __construct(int $type)
    {
        $this->type = $type;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function getQuery(): string
    {
        return (string)new QueryBuilder(
            new QueryParam(QueryParams::EVENT_TYPE, self::EVENT_TYPE),
            new QueryParam(QueryParams::OS, self::$typeNames[$this->type] ?? ""),
            new QueryParam(QueryParams::OS_INDEX, (string)$this->type),
            new QueryParam(QueryParams::NONCE, $this->getNonce()),
        );
    }
}
