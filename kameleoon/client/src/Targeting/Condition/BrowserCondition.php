<?php

declare(strict_types=1);

namespace Kameleoon\Targeting\Condition;

use Kameleoon\Data\Browser;
use Kameleoon\Helpers\SdkVersion;

class BrowserCondition extends TargetingCondition
{
    const TYPE = "BROWSER";

    const CHROME = "CHROME";
    const INTERNET_EXPLORER = "IE";
    const FIREFOX = "FIREFOX";
    const SAFARI = "SAFARI";
    const OPERA = "OPERA";

    private int $browserType;
    private ?string $version;
    private ?string $operator;

    public function __construct($conditionData)
    {
        parent::__construct($conditionData);
        $this->browserType = $this->browserTypeFromBrowser($conditionData->browser ?? "");
        $this->version = $conditionData->version ?? null;
        $this->operator = $conditionData->versionMatchType ?? null;
    }

    public function check($data): bool
    {
        return $data instanceof Browser && $this->checkTargeting($data);
    }

    private function checkTargeting(Browser $browser)
    {
        if ($this->browserType != $browser->getBrowserType()) {
            return false;
        }

        if ($this->version === null) {
            return true;
        }

        $versionNumber = SdkVersion::getFloatVersion($this->version);
        if (is_nan($versionNumber)) {
            return false;
        }

        switch ($this->operator) {
            case TargetingOperator::EQUAL:
                return $browser->getVersion() == $versionNumber;
            case TargetingOperator::GREATER:
                return $browser->getVersion() > $versionNumber;
            case TargetingOperator::LOWER:
                return $browser->getVersion() < $versionNumber;
            default:
                error_log("Unexpected comparing operation for Browser condition: " . $this->operator);
                return false;
        }
    }

    private function browserTypeFromBrowser(string $browserType): int
    {
        $browserType = $browserType == "IE" ? "INTERNET_EXPLORER" : $browserType;
        return Browser::$browsers[$browserType] ?? Browser::OTHER;
    }
}
