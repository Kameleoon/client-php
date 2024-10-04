<?php

namespace Kameleoon\Logging;

interface Logger
{
    public function log(int $level, string $message);
}
