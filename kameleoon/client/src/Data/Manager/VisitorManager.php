<?php

declare(strict_types=1);

namespace Kameleoon\Data\Manager;

use IteratorAggregate;

interface VisitorManager extends IteratorAggregate
{
    /**
     * Retrieves an existing visitor identified by the specified code.
     *
     * @param string $visitorCode The unique code identifying the visitor.
     * @return Visitor|null The existing visitor if found, or null if not found.
     */
    public function getVisitor(string $visitorCode): ?Visitor;

    /**
     * Retrieves an existing visitor identified by the specified code, or creates a new one if not
     * found.
     *
     * @param string $visitorCode The unique code identifying the visitor.
     * @return Visitor The existing visitor if found, or a new visitor instance if not found.
     */
    public function getOrCreateVisitor(string $visitorCode): Visitor;
}
