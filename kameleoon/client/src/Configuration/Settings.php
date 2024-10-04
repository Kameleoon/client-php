<?php

declare(strict_types=1);

namespace Kameleoon\Configuration;

use Kameleoon\Logging\KameleoonLogger;

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
            KameleoonLogger::error("Configuration object is missed in server response: " . json_encode($json));
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

    public function __toString(): string
    {
        return sprintf(
            "Settings{consentRequired:%s,dataApiDomain:'%s'}",
            $this->consentRequired ? "true" : "false",
            $this->dataApiDomain,
        );
    }
}
