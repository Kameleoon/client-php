<?php

declare(strict_types=1);

namespace Kameleoon\Types;

use Kameleoon\Helpers\StringHelper;

class Rule
{
    /**
     * @var array<string, Variation>
     */
    public array $variations;

    /**
     * @internal
     * @param array<string, Variation> $variations
     */
    public function __construct(array $variations)
    {
        $this->variations = $variations;
    }

    public function __toString(): string
    {
        $variations = StringHelper::sarray($this->variations);
        return "Rule{variations:$variations}";
    }
}
