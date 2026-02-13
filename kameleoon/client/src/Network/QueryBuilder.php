<?php

declare(strict_types=1);

namespace Kameleoon\Network;

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
