<?php

declare(strict_types=1);

namespace Kameleoon\Data;

class Cookie implements Data
{
    private array $cookies;

    public function __construct(array $cookies)
    {
        $this->cookies = $cookies;
    }

    public function getCookies(): array
    {
        return $this->cookies;
    }

    public function __toString(): string
    {
        return "Cookie{cookies:" . implode(',', $this->cookies) . "}";
    }
}
