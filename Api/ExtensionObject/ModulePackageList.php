<?php

declare(strict_types=1);

namespace Ecotone\Api\ExtensionObject;

/**
 * licence Apache-2.0
 */
final class ModulePackageList
{
    public const CORE_PACKAGE = 'core';
    public const AMQP_PACKAGE = 'amqp';
    public const DATA_PROTECTION_PACKAGE = 'dataProtection';
    public const DBAL_PACKAGE = 'dbal';
    public const REDIS_PACKAGE = 'redis';
    public const SQS_PACKAGE = 'sqs';
    public const KAFKA_PACKAGE = 'kafka';
    public const EVENT_SOURCING_PACKAGE = 'eventSourcing';
    public const JMS_CONVERTER_PACKAGE = 'jmsConverter';
    public const TRACING_PACKAGE = 'tracing';
    public const LARAVEL_PACKAGE = 'laravel';
    public const SYMFONY_PACKAGE = 'symfony';
    public const TEMPEST_PACKAGE = 'tempest';
    public const TEST_PACKAGE = 'test';
}
