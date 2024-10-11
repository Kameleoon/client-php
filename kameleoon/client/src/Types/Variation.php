<?php
declare(strict_types=1);

namespace Kameleoon\Types;

use Kameleoon\Configuration;
use Kameleoon\Helpers\StringHelper;

class Variation
{
    /**
     * @var string
     */
    public string $key;

    /**
     * @var ?int
     */
    public ?int $id;

    /**
     * @var ?int
     */
    public ?int $experimentId;

    /**
     * @var array<string, Variable>
     */
    public array $variables;

    /**
     * @param string $key
     * @param ?int $id
     * @param ?int $experimentId
     * @param array<string, Variable> $variables
     */
    public function __construct(string $key, ?int $id, ?int $experimentId, array $variables)
    {
        $this->key = $key;
        $this->id = $id;
        $this->experimentId = $experimentId;
        $this->variables = $variables;
    }

    /**
     * @return bool `true` if the variation is active, otherwise `false`.
     */
    public function isActive(): bool
    {
        return $this->key != Configuration\Variation::VARIATION_OFF;
    }

    public function __toString(): string {
        $variables = StringHelper::objectToString($this->variables);
        return "Variation{key:'$this->key',id:$this->id,experimentId:$this->experimentId,variables:$variables}";
    }
}
