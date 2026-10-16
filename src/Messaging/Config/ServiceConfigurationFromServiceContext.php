<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Ecotone\Api\ExtensionObject\ServiceConfiguration;

/**
 * licence Apache-2.0
 */
final class ServiceConfigurationFromServiceContext
{
    public const ONLY_ONE_IS_READ = 'Ecotone reads exactly one ServiceConfiguration, so it cannot tell which serviceName, defaultSerializationMediaType, defaultErrorChannel, defaultMemoryLimit, connectionRetryTemplate or headersInLogContext to apply. ';

    public function __construct(
        public readonly ServiceConfiguration $serviceConfiguration,
        public readonly string $className,
        public readonly string $methodName,
    ) {
    }

    public function origin(): string
    {
        return "{$this->className}::{$this->methodName}()";
    }
}
