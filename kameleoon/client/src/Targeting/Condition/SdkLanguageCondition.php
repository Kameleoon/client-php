<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

class SdkLanguageCondition extends VersionCondition
{
    const TYPE = "SDK_LANGUAGE";

    private $sdkLanguage;

    public function __construct($conditionData)
    {
        parent::__construct($conditionData);
        $this->sdkLanguage = $conditionData->sdkLanguage ?? '';
    }

    public function check($data): bool
    {
        return $data instanceof SdkInfo && $this->checkTargeting($data);
    }

    private function checkTargeting(SdkInfo $sdkInfo)
    {
        return $this->sdkLanguage === $sdkInfo->getSdkLanguage()
            && ($this->conditionVersion === null || $this->compareWithVersion($sdkInfo->getVersion()));
    }
}

class SdkInfo
{
    private string $sdkLanguage;
    private string $version;

    public function getSdkLanguage(): string
    {
        return $this->sdkLanguage;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function __construct(string $sdkLanguage, string $version)
    {
        $this->sdkLanguage = $sdkLanguage;
        $this->version = $version;
    }
}
