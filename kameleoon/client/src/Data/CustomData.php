<?php
namespace Kameleoon\Data;

use Kameleoon\KameleoonClientImpl;
use Kameleoon\Network\QueryBuilder;
use Kameleoon\Network\QueryParam;
use Kameleoon\Network\QueryParams;

class CustomData implements DataInterface
{
    public const EVENT_TYPE = "customData";

    private $id;
    private array $values;
    private $nonce;

    public function __construct(int $id, string ...$values)
    {
        $this->id = $id;
        $this->values = $values;
        $this->nonce = KameleoonClientImpl::obtainNonce();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function obtainFullPostTextLine(): string
    {
        if (count($this->values) === 0) {
            return "";
        }
        $arrayBuilder = array();
        foreach ($this->values as $val) {
            $arrayBuilder[$val] = 1;
        }
        $encoded = json_encode($arrayBuilder, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        //$encoded = str_replace("\\", "", $encoded);
        return (string)new QueryBuilder(
            new QueryParam(QueryParams::EVENT_TYPE, self::EVENT_TYPE),
            new QueryParam(QueryParams::INDEX, (string)$this->id),
            new QueryParam(QueryParams::VALUES_COUNT_MAP, $encoded),
            new QueryParam(QueryParams::OVERWRITE, "true"),
            new QueryParam(QueryParams::NONCE, $this->nonce),
        );
    }
}
