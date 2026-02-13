<?php

declare(strict_types=1);

namespace Kameleoon\Helpers;

class SemVersion
{
    private const NON_EXISTENT_IDENTIFIER = -1;

    public int $major;
    public int $minor;
    public int $patch;

    public function __construct(
        int $major = self::NON_EXISTENT_IDENTIFIER,
        int $minor = self::NON_EXISTENT_IDENTIFIER,
        int $patch = self::NON_EXISTENT_IDENTIFIER
    ) {
        $this->major = $major;
        $this->minor = $minor;
        $this->patch = $patch;
    }

    /**
     * Compares this version to another SemVersion.
     * Returns < 0 if lower, 0 if equal, > 0 if higher.
     */
    public function compareTo(SemVersion $v): int
    {
        if ($this->major !== $v->major) {
            return $this->major <=> $v->major;
        }

        if ($this->minor !== $v->minor) {
            return $this->minor <=> $v->minor;
        }

        return $this->patch <=> $v->patch;
    }

    public function toFloat(): float
    {
        // Force dot as decimal separator
        return (float) sprintf('%d.%d', $this->major, $this->minor);
    }

    public static function fromString(string $versionString): ?SemVersion
    {
        $versions = [0, 0, 0];
        $versionParts = explode('.', $versionString);

        for ($i = 0; $i < count($versions) && $i < count($versionParts); $i++) {
            if (!ctype_digit($versionParts[$i])) {
                // Replace with your logger if needed
                error_log(sprintf(
                    "Invalid version component, index: %d, value: '%s'",
                    $i,
                    $versionParts[$i]
                ));
                return null;
            }

            $versions[$i] = (int) $versionParts[$i];
        }

        return new SemVersion($versions[0], $versions[1], $versions[2]);
    }
}
