<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Helpers\SdkVersion;

class SdkLanguageCondition extends TargetingCondition
{
    const TYPE = "SDK_LANGUAGE";

    private $sdkLanguage;
    private $version;
    private $operator;

    public function __construct($conditionData)
    {
        parent::__construct($conditionData);
        $this->sdkLanguage = $conditionData->sdkLanguage ?? '';
        $this->version = $conditionData->version ?? null;
        $this->operator = $conditionData->versionMatchType ?? null;
    }

    public function check($data): bool
    {
        return $data instanceof SdkInfo && $this->checkTargeting($data);
    }

    private function checkTargeting(SdkInfo $sdkInfo)
    {
        if ($this->sdkLanguage !== $sdkInfo->getSdkLanguage()) {
            return false;
        }

        if ($this->version === null) {
            return true;
        }

        $versionComponentsCondition = SdkVersion::getVersionComponents($this->version);
        $versionComponentsSdk = SdkVersion::getVersionComponents($sdkInfo->getVersion());

        if ($versionComponentsCondition === null || $versionComponentsSdk === null) {
            return false;
        }

        $majorCondition = $versionComponentsCondition[0];
        $minorCondition = $versionComponentsCondition[1];
        $patchCondition = $versionComponentsCondition[2];

        $majorSdk = $versionComponentsSdk[0];
        $minorSdk = $versionComponentsSdk[1];
        $patchSdk = $versionComponentsSdk[2];

        switch ($this->operator) {
            case TargetingOperator::EQUAL:
                return $majorSdk === $majorCondition && $minorSdk === $minorCondition && $patchSdk === $patchCondition;
            case TargetingOperator::GREATER:
                return $majorSdk > $majorCondition
                    || ($majorSdk === $majorCondition && $minorSdk > $minorCondition)
                    || ($majorSdk === $majorCondition && $minorSdk === $minorCondition && $patchSdk > $patchCondition);
            case TargetingOperator::LOWER:
                return $majorSdk < $majorCondition
                    || ($majorSdk === $majorCondition && $minorSdk < $minorCondition)
                    || ($majorSdk === $majorCondition && $minorSdk === $minorCondition && $patchSdk < $patchCondition);
            default:
                error_log("Unexpected comparing operation for SdkLanguage condition: " . $this->operator . PHP_EOL);
                return false;
        }
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
