<?php

namespace Kameleoon\Data;

use Kameleoon\Helpers\StringHelper;
use Kameleoon\Logging\KameleoonLogger;
use Kameleoon\Network\QueryBuilder;
use Kameleoon\Network\QueryParam;
use Kameleoon\Network\QueryParams;
use Kameleoon\Network\Sendable;

class CustomData extends Sendable implements Data
{
    public const EVENT_TYPE = "customData";

    /** @internal */
    public const UNDEFINED_INDEX = -1;

    protected $index;
    protected ?string $name;
    protected array $values;
    protected bool $overwrite;

    /**
     * @param int|string $indexOrName
     */
    public function __construct($indexOrName, string ...$values)
    {
        if (is_int($indexOrName)) {
            $this->index = $indexOrName;
            $this->name = null;
        } elseif (is_string($indexOrName)) {
            $this->index = self::UNDEFINED_INDEX;
            $this->name = $indexOrName;
        } else {
            $this->index = self::UNDEFINED_INDEX;
            $this->name = null;
            KameleoonLogger::error("Cannot initialize CustomData: unexpected type of 'indexOrName' parameter");
        }
        $this->values = $values;
        $this->overwrite = true;
    }

    /**
     * @param int|string $indexOrName
     */
    public static function newWithOverwrite($indexOrName, bool $overwrite, string ...$values): CustomData
    {
        $cd = new CustomData($indexOrName, ...$values);
        $cd->overwrite = $overwrite;
        return $cd;
    }

    /** @internal */
    public function namedToIndexed(int $index): CustomData
    {
        $cd = new CustomData($index, ...$this->values);
        $cd->name = $this->name;
        $cd->overwrite = $this->overwrite;
        return $cd;
    }

    /**
     * @deprecated Please use `getIndex` instead
     */
    public function getId()
    {
        return $this->index;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function getOverwrite(): bool
    {
        return $this->overwrite;
    }

    /** @internal */
    public function getQuery(): string
    {
        $arrayBuilder = [];
        foreach ($this->values as $val) {
            $arrayBuilder[$val] = 1;
        }
        $encoded = json_encode((object)$arrayBuilder, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        //$encoded = str_replace("\\", "", $encoded);
        $qb = new QueryBuilder(
            new QueryParam(QueryParams::EVENT_TYPE, self::EVENT_TYPE),
            new QueryParam(QueryParams::INDEX, (string)$this->index),
            new QueryParam(QueryParams::VALUES_COUNT_MAP, $encoded),
            new QueryParam(QueryParams::OVERWRITE, StringHelper::sbool($this->overwrite)),
            new QueryParam(QueryParams::NONCE, $this->getNonce()),
        );
        return (string)$qb;
    }

    public function __toString(): string
    {
        return "CustomData{index:$this->index,name:'$this->name',values:" . json_encode($this->values)
            . ",overwrite:" . StringHelper::sbool($this->overwrite) . "}";
    }
}
