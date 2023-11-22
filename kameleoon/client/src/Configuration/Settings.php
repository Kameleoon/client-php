<?php

declare(strict_types=1);

namespace Kameleoon\Configuration;

class Settings
{
    const CONSENT_TYPE_REQUIRED = "REQUIRED";

    private ?string $consentType;

    public function __construct(object $json)
    {
        if (isset($json->configuration)) {
            $settings = $json->configuration;
            $this->consentType = isset($settings->consentType) ? $settings->consentType : null;
        } else {
            error_log("Kameleoon SDK: Configuration object is missed in server response: " . json_encode($json));
        }
    }

    public function isConsentRequired(): bool
    {
        return isset($this->consentType) ? $this->consentType === Settings::CONSENT_TYPE_REQUIRED : false;
    }
}
