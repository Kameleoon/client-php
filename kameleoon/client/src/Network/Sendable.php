<?php

namespace Kameleoon\Network;

use Kameleoon\Helpers\RandomString;

abstract class Sendable implements QueryPresentable
{
    private $sent = false;
    private $nonce;

    public function isSent(): bool
    {
        return $this->sent;
    }

    public function markAsSent(): void
    {
        $this->sent = true;
        $this->nonce = null;
    }

    protected function getNonce(): string
    {
        if ($this->nonce === null) {
            $this->nonce = RandomString::obtainNonce();
        }
        return $this->nonce;
    }

    abstract public function getQuery(): string;
}
