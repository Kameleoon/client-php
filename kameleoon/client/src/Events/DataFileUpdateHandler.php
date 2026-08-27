<?php

declare(strict_types=1);

namespace Kameleoon\Events;

/**
 * Handler of SDK data file update events.
 */
interface DataFileUpdateHandler extends EventHandler
{
    /**
     * Called when the SDK configuration (data file) is updated.
     */
    public function onUpdate(DataFileUpdateEvent $event): void;
}
