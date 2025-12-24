<?php

namespace Kameleoon\Helpers;

define("VERSION_SDK", "4.18.0");

class SdkVersion
{
    public const SDK_NAME = "PHP";

    private static $version;

    public static function getName()
    {
        return self::SDK_NAME;
    }

    public static function getVersion()
    {
        if (self::$version === null) {
            self::readVersion();
        }
        return self::$version;
    }

    private static function readVersion()
    {
        self::$version = VERSION_SDK;
        // TODO: fix in https://project.kameleoon.net/issues/23069
        // self::$version = \Composer\InstalledVersions::getVersion('kameleoon/kameleoon-client-php');

        // if (empty(self::$version)) {
        //     echo 'Kameleoon SDK: Version of SDK is not defined';
        // }
    }

    public static function getVersionComponents(string $versionString): ?array
    {
        $versions = [0, 0, 0];

        $versionParts = explode('.', $versionString);
        for ($i = 0; $i < count($versions) && $i < count($versionParts); $i++) {
            if (ctype_digit($versionParts[$i])) {
                $versions[$i] = (int)$versionParts[$i];
            } else {
                return null;
            }
        }
        return $versions;
    }

    public static function getFloatVersion($versionString)
    {
        $versionComponents = self::getVersionComponents($versionString);
        if ($versionComponents === null) {
            return NAN;
        }
        $major = $versionComponents[0];
        $minor = $versionComponents[1];
        return (float)("$major.$minor");
    }
}
