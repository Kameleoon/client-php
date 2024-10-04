<?php

declare(strict_types=1);

namespace Kameleoon\Logging;

use Kameleoon\Helpers\StringHelper;

class KameleoonLogger
{

    private static Logger $logger;
    private static int $logLevel;

    private static function init(): void
    {
        self::$logger = new DefaultLogger();
        self::$logLevel = LogLevel::WARNING;
    }

    public static function getLogger(): Logger
    {
        if (!isset(self::$logger)) {
            self::init();
        }
        return self::$logger;
    }

    public static function setLogger(Logger $logger): void
    {
        self::$logger = $logger;
    }

    public static function getLogLevel(): int
    {
        if (!isset(self::$logLevel)) {
            self::init();
        }
        return self::$logLevel;
    }

    public static function setLogLevel(int $logLevel): void
    {
        if (!isset(self::$logLevel)) {
            self::init();
        }
        self::$logLevel = $logLevel;
    }

    public static function log($level, $data, ...$args): void
    {
        if (self::checkLevel($level)) {
            if (is_callable($data)) {
                $message = $data();
            } else {
                if (count($args) == 0) {
                    $message = $data;
                } else {
                    $message = sprintf($data, ...StringHelper::prepareArgs(...$args));
                }
            }
            self::writeMessage($level, $message);
        }
    }

    public static function info($data, ...$args): void
    {
        self::log(LogLevel::INFO, $data, ...$args);
    }

    public static function error($data, ...$args): void
    {
        self::log(LogLevel::ERROR, $data, ...$args);
    }

    public static function warning($data, ...$args): void
    {
        self::log(LogLevel::WARNING, $data, ...$args);
    }

    public static function debug($data, ...$args): void
    {
        self::log(LogLevel::DEBUG, $data, ...$args);
    }

    private static function checkLevel($level): bool
    {
        return $level <= self::getLogLevel() && $level != LogLevel::NONE;
    }

    private static function writeMessage($level, $message): void
    {
        $levelName = LogLevel::getLevelName($level);
        self::$logger->log($level, "Kameleoon [$levelName]: $message");
    }
}
