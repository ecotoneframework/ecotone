<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

/**
 * licence Apache-2.0
 */
final class ModulePackageList
{
    public const CORE_PACKAGE = 'core';
    /**
     * @TODO Ecotone 2.0 add to Core package
     */
    public const ASYNCHRONOUS_PACKAGE = 'asynchronous';
    public const AMQP_PACKAGE = 'amqp';
    public const DBAL_PACKAGE = 'dbal';
    public const REDIS_PACKAGE = 'redis';
    public const SQS_PACKAGE = 'sqs';
    public const KAFKA_PACKAGE = 'kafka';
    public const EVENT_SOURCING_PACKAGE = 'eventSourcing';
    public const JMS_CONVERTER_PACKAGE = 'jmsConverter';
    public const TRACING_PACKAGE = 'tracing';
    public const LARAVEL_PACKAGE = 'laravel';
    public const SYMFONY_PACKAGE = 'symfony';
    public const TEST_PACKAGE = 'test';

    public static function getModuleClassesForPackage(string $packageName): array
    {
        return match ($packageName) {
            ModulePackageList::CORE_PACKAGE => ModuleClassList::CORE_MODULES,
            ModulePackageList::ASYNCHRONOUS_PACKAGE => ModuleClassList::ASYNCHRONOUS_MODULE,
            ModulePackageList::AMQP_PACKAGE => ModuleClassList::AMQP_MODULES,
            ModulePackageList::DBAL_PACKAGE => ModuleClassList::DBAL_MODULES,
            ModulePackageList::REDIS_PACKAGE => ModuleClassList::REDIS_MODULES,
            ModulePackageList::SQS_PACKAGE => ModuleClassList::SQS_MODULES,
            ModulePackageList::KAFKA_PACKAGE => ModuleClassList::KAFKA_MODULES,
            ModulePackageList::EVENT_SOURCING_PACKAGE => ModuleClassList::EVENT_SOURCING_MODULES,
            ModulePackageList::JMS_CONVERTER_PACKAGE => ModuleClassList::JMS_CONVERTER_MODULES,
            ModulePackageList::TRACING_PACKAGE => ModuleClassList::TRACING_MODULES,
            ModulePackageList::TEST_PACKAGE => ModuleClassList::TEST_MODULES,
            ModulePackageList::LARAVEL_PACKAGE => ModuleClassList::LARAVEL_MODULES,
            ModulePackageList::SYMFONY_PACKAGE => ModuleClassList::SYMFONY_MODULES,
            default => throw ConfigurationException::create(sprintf('Given unknown package name %s. Available packages name are: %s', $packageName, implode(',', self::allPackages())))
        };
    }

    /**
     * @return string[]
     */
    public static function allPackages(): array
    {
        return [
            self::CORE_PACKAGE,
            self::ASYNCHRONOUS_PACKAGE,
            self::AMQP_PACKAGE,
            self::REDIS_PACKAGE,
            self::SQS_PACKAGE,
            self::KAFKA_PACKAGE,
            self::DBAL_PACKAGE,
            self::EVENT_SOURCING_PACKAGE,
            self::JMS_CONVERTER_PACKAGE,
            self::TRACING_PACKAGE,
            self::LARAVEL_PACKAGE,
            self::SYMFONY_PACKAGE,
        ];
    }

    public static function allPackagesExcept(array $modulePackageNames): array
    {
        return array_diff(self::allPackages(), $modulePackageNames);
    }
}
