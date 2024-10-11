<?php
declare(strict_types=1);

namespace Kameleoon\Types;

use Kameleoon\Helpers\StringHelper;

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

    public function __toString(): string {
        if (is_string($this->value)) {
            $value = "'$this->value'";
        } else {
            $value = StringHelper::objectToString($this->value);
        }
        return "Variable{key:'$this->key',type:'$this->type',value:$value}";
    }
}
