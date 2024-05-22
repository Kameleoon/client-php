<?php

declare(strict_types=1);

namespace Kameleoon\Data\Manager;

use IteratorAggregate;
use Kameleoon\Configuration\CustomDataInfo;
use Kameleoon\Data\BaseData;

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

    /**
     * Adds data by a visitor code.
     *
     * @param string $visitorCode A visitor code to store the data by.
     * @param BaseData ...$data An array of Data objects to be stored.
     *
     * @return Visitor A visitor the data was added for.
     */
    public function addData(string $visitorCode, BaseData ...$data): Visitor;

    /**
     * Gets custom data info.
     *
     * @return ?CustomDataInfo
     */
    public function getCustomDataInfo(): ?CustomDataInfo;

    /**
     * Sets or removes custom data info.
     *
     * @param ?CustomDataInfo $value
     */
    public function setCustomDataInfo(?CustomDataInfo $value): void;
}
