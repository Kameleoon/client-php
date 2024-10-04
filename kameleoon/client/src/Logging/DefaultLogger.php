<?php

declare(strict_types=1);

namespace Kameleoon\Logging;

class DefaultLogger implements Logger
{
    public function log(int $level, string $message): void
    {
        error_log($message);
    }
}
