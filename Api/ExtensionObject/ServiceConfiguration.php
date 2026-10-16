<?php

declare(strict_types=1);

namespace Ecotone\Api\ExtensionObject;

use Ecotone\Messaging\Config\ConfigurationException;

/**
 * licence Apache-2.0
 */
final class ServiceConfiguration
{
    public const DEFAULT_SERVICE_NAME = 'ecotoneService';
    public const DEFAULT_SERIALIZATION_MEDIA_TYPE = MediaType::APPLICATION_X_PHP_SERIALIZED;

    /**
     * @param array<int, string> $headersInLogContext
     */
    private function __construct(
        public readonly string $serviceName = self::DEFAULT_SERVICE_NAME,
        public readonly ?string $defaultSerializationMediaType = null,
        public readonly ?string $defaultErrorChannel = null,
        public readonly ?int $defaultMemoryLimitInMegabytes = PollingMetadata::DEFAULT_MEMORY_LIMIT_MEGABYTES,
        public readonly ?RetryTemplateBuilder $connectionRetryTemplate = null,
        public readonly array $headersInLogContext = [],
    ) {
    }

    public static function createWithDefaults(): self
    {
        return new self();
    }

    public function withServiceName(string $serviceName): self
    {
        if (! preg_match('#[a-z0-9_]#', $serviceName)) {
            throw ConfigurationException::create("Service name can only be used with lower case letter, numbers and underline, given: `{$serviceName}`");
        }

        return new self(...[...get_object_vars($this), 'serviceName' => $serviceName]);
    }

    public function withDefaultSerializationMediaType(string|MediaType $defaultSerializationMediaType): self
    {
        return new self(...[...get_object_vars($this), 'defaultSerializationMediaType' => is_string($defaultSerializationMediaType) ? $defaultSerializationMediaType : $defaultSerializationMediaType->toString()]);
    }

    public function withDefaultErrorChannel(string $errorChannelName): self
    {
        return new self(...[...get_object_vars($this), 'defaultErrorChannel' => $errorChannelName]);
    }

    public function withConsumerMemoryLimit(int $memoryLimitInMegabytes): self
    {
        return new self(...[...get_object_vars($this), 'defaultMemoryLimitInMegabytes' => $memoryLimitInMegabytes]);
    }

    public function withConnectionRetryTemplate(RetryTemplateBuilder $channelPollRetryTemplate): self
    {
        return new self(...[...get_object_vars($this), 'connectionRetryTemplate' => $channelPollRetryTemplate]);
    }

    /**
     * @param array<int, string> $headerNames
     */
    public function withHeadersInLogContext(array $headerNames): self
    {
        foreach ($headerNames as $headerName) {
            if (! is_string($headerName) || $headerName === '') {
                throw ConfigurationException::create("withHeadersInLogContext() was given an empty header name. Pass the exact name of each message header to add to the log context, for example withHeadersInLogContext(['userId']), or remove the empty entry.");
            }
        }

        return new self(...[...get_object_vars($this), 'headersInLogContext' => array_values(array_unique($headerNames))]);
    }
}
