<?php
declare(strict_types=1);

namespace Kameleoon\Types;

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

}
