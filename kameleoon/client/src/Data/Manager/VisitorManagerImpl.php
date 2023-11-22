<?php

declare(strict_types=1);

namespace Kameleoon\Data\Manager;

use Traversable;
use Kameleoon\Data\Manager\VisitorImpl;

class VisitorManagerImpl implements VisitorManager
{
    private array $visitors;

    public function __construct()
    {
        $this->visitors = [];
    }

    public function getOrCreateVisitor($visitorCode): Visitor
    {
        $visitor = $this->visitors[$visitorCode] ?? null;
        if (is_null($visitor)) {
            $visitor = new VisitorImpl();
            $this->visitors[$visitorCode] = $visitor;
        }
        return $visitor;
    }

    public function getVisitor($visitorCode): ?Visitor
    {
        return $this->visitors[$visitorCode] ?? null;
    }

    public function getIterator(): Traversable
    {
        foreach ($this->visitors as $value) {
            yield $value;
        }
    }
}
