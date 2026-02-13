<?php

declare(strict_types=1);

namespace Kameleoon\Network;

use Kameleoon\Helpers\URLEncoding;

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
