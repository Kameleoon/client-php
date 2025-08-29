<?php

namespace Kameleoon\Data;

use Kameleoon\Network\QueryParam;
use Kameleoon\Network\QueryParams;

/** @internal */
class MappingIdentifier extends CustomData
{
    public function __construct(CustomData $customData)
    {
        parent::__construct($customData->index, ...$customData->values);
        $this->name = $customData->name;
        $this->overwrite = $customData->overwrite;
    }

    public function isSent(): bool
    {
        return false;
    }

    public function getQuery(): string
    {
        return parent::getQuery() . "&" . new QueryParam(QueryParams::MAPPING_IDENTIFIER, "true");
    }

    public function __toString(): string
    {
        $id = $this->getIndex();
        $values = json_encode($this->getValues());
        return "MappingIdentifier{id:$id,values:$values}";
    }
}
