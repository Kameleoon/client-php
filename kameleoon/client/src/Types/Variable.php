<?php
declare(strict_types=1);

namespace Kameleoon\Types;

class Variable
{
    /**
     * @var string
     */
    public string $key;

    /**
     * @var string
     */
    public string $type;

    /**
     * @var ?mixed
     */
    public $value;

    /**
     * @param string $key
     * @param string $type
     * @param ?mixed $value
     */
    public function __construct(string $key, string $type, $value)
    {
        $this->key = $key;
        $this->type = $type;
        $this->value = $value;
    }

}
