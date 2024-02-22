<?php

declare(strict_types=1);

namespace Kameleoon\Configuration;

class Settings
{
    const CONSENT_TYPE_REQUIRED = "REQUIRED";

    private bool $consentRequired = false;
    private ?string $dataApiDomain;

    public function __construct(object $json)
    {
        if (isset($json->configuration)) {
            $settings = $json->configuration;
            $this->consentRequired =
                isset($settings->consentType) ? $settings->consentType === Settings::CONSENT_TYPE_REQUIRED : false;
            $this->dataApiDomain = isset($settings->dataApiDomain) ? $settings->dataApiDomain : null;
        } else {
            error_log("Kameleoon SDK: Configuration object is missed in server response: " . json_encode($json));
        }
    }

    public function isConsentRequired(): bool
    {
        return $this->consentRequired;
    }

    public function getDataApiDomain(): ?string
    {
        return $this->dataApiDomain;
    }
}
