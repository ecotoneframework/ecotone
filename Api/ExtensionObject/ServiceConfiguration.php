<?php

declare(strict_types=1);

namespace Ecotone\Api\ExtensionObject;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Config\ModuleClassList;
use Ecotone\Messaging\Config\ServiceCacheConfiguration;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Apache-2.0
 */
final class ServiceConfiguration
{
    public const DEFAULT_SERVICE_NAME = 'ecotoneService';
    public const DEFAULT_ENVIRONMENT              = 'dev';
    public const DEFAULT_FAIL_FAST                = true;
    public const DEFAULT_SERIALIZATION_MEDIA_TYPE = MediaType::APPLICATION_X_PHP_SERIALIZED;

    /**
     * @param string[] $namespaces
     * @param string[]|null $enabledModulePackageNames
     * @param object[] $extensionObjects
     * @param object[] $pollableEndpointAnnotations
     * @param array<int, string> $headersInLogContext
     */
    private function __construct(
        public readonly string $cacheDirectoryPath,
        public readonly string $serviceName = self::DEFAULT_SERVICE_NAME,
        public readonly bool $failFast = self::DEFAULT_FAIL_FAST,
        public readonly string $environment = self::DEFAULT_ENVIRONMENT,
        public readonly ?string $loadCatalog = '',
        public readonly array $namespaces = [],
        private readonly ?array $enabledModulePackageNames = null,
        public readonly ?string $defaultSerializationMediaType = null,
        public readonly ?string $defaultErrorChannel = null,
        public readonly ?int $defaultMemoryLimitInMegabytes = PollingMetadata::DEFAULT_MEMORY_LIMIT_MEGABYTES,
        public readonly ?RetryTemplateBuilder $connectionRetryTemplate = null,
        public readonly array $extensionObjects = [],
        public readonly array $pollableEndpointAnnotations = [],
        public readonly ?string $licenceKey = null,
        public readonly array $headersInLogContext = [],
    ) {
    }

    public static function createWithDefaults(): self
    {
        return new self(ServiceCacheConfiguration::defaultCachePath());
    }

    /**
     * @param self[] $applicationConfigurations
     */
    public function mergeWith(array $applicationConfigurations): self
    {
        $self = $this;

        if (! $this->defaultSerializationMediaType) {
            $defaultSerializationMediaType = null;
            foreach ($applicationConfigurations as $applicationConfiguration) {
                if ($applicationConfiguration->defaultSerializationMediaType) {
                    if ($defaultSerializationMediaType && ($applicationConfiguration->defaultSerializationMediaType !== $defaultSerializationMediaType)) {
                        throw ConfigurationException::create("Ecotone can't resolve defaultSerializationMediaType. In order to continue you need to set it up.");
                    }
                    $defaultSerializationMediaType = $applicationConfiguration->defaultSerializationMediaType;
                }
            }

            $self = $self->withDefaultSerializationMediaType($defaultSerializationMediaType ?? self::DEFAULT_SERIALIZATION_MEDIA_TYPE);
        }

        if (! $this->defaultErrorChannel) {
            $defaultErrorChannel = null;
            foreach ($applicationConfigurations as $applicationConfiguration) {
                if ($applicationConfiguration->defaultErrorChannel) {
                    if ($defaultErrorChannel && $applicationConfiguration->defaultErrorChannel !== $defaultErrorChannel) {
                        throw ConfigurationException::create("Ecotone can't resolve defaultErrorChannel. In order to continue you need to set it up.");
                    }
                    $defaultErrorChannel = $applicationConfiguration->defaultErrorChannel;
                }
            }

            if ($defaultErrorChannel) {
                $self = $self->withDefaultErrorChannel($defaultErrorChannel);
            }
        }

        foreach ($applicationConfigurations as $applicationConfiguration) {
            $self = $self->withHeadersInLogContext([...$self->headersInLogContext, ...$applicationConfiguration->headersInLogContext]);
        }

        return $self;
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

    public function withFailFast(bool $failFast): self
    {
        return new self(...[...get_object_vars($this), 'failFast' => $failFast]);
    }

    public function withServiceName(string $serviceName): self
    {
        if (! preg_match('#[a-z0-9_]#', $serviceName)) {
            throw ConfigurationException::create("Service name can only be used with lower case letter, numbers and underline, given: `{$serviceName}`");
        }

        return new self(...[...get_object_vars($this), 'serviceName' => $serviceName]);
    }

    public function withEnvironment(string $environment): self
    {
        return new self(...[...get_object_vars($this), 'environment' => $environment]);
    }

    public function withLoadCatalog(string $catalog): self
    {
        return new self(...[...get_object_vars($this), 'loadCatalog' => $catalog]);
    }

    public function withExtensionObjects(array $extensionObjects): self
    {
        foreach ($extensionObjects as $extensionObject) {
            Assert::isObject($extensionObject, 'Extension object must be an object, given: ' . gettype($extensionObject));
        }

        return new self(...[...get_object_vars($this), 'extensionObjects' => $extensionObjects]);
    }

    public function addExtensionObject(object $extensionObject): self
    {
        return new self(...[...get_object_vars($this), 'extensionObjects' => [...$this->extensionObjects, $extensionObject]]);
    }

    public function doNotLoadCatalog(): self
    {
        return new self(...[...get_object_vars($this), 'loadCatalog' => null]);
    }

    public function isLoadingCatalogEnabled(): bool
    {
        return ! is_null($this->loadCatalog);
    }

    /**
     * @param string[] $namespaces
     */
    public function withNamespaces(array $namespaces): self
    {
        return new self(...[...get_object_vars($this), 'namespaces' => $namespaces]);
    }

    public function withDefaultSerializationMediaType(string|MediaType $defaultSerializationMediaType): self
    {
        return new self(...[...get_object_vars($this), 'defaultSerializationMediaType' => is_string($defaultSerializationMediaType) ? $defaultSerializationMediaType : $defaultSerializationMediaType->toString()]);
    }

    public function withDefaultErrorChannel(string $errorChannelName): self
    {
        return new self(...[...get_object_vars($this), 'defaultErrorChannel' => $errorChannelName]);
    }

    public function withConnectionRetryTemplate(RetryTemplateBuilder $channelPollRetryTemplate): self
    {
        return new self(...[...get_object_vars($this), 'connectionRetryTemplate' => $channelPollRetryTemplate]);
    }

    public function withLicenceKey(string $licenceKey): self
    {
        return new self(...[...get_object_vars($this), 'licenceKey' => $licenceKey]);
    }

    public function isRunningForEnterprise(): bool
    {
        return $this->licenceKey !== null;
    }

    /**
     * @param string[] $enabledModulePackageNames
     */
    public function withEnabledModulePackageNames(array $enabledModulePackageNames): self
    {
        return new self(...[...get_object_vars($this), 'enabledModulePackageNames' => array_values(array_unique([...$enabledModulePackageNames, ModulePackageList::CORE_PACKAGE]))]);
    }

    public function withConsumerMemoryLimit(int $memoryLimitInMegabytes): self
    {
        return new self(...[...get_object_vars($this), 'defaultMemoryLimitInMegabytes' => $memoryLimitInMegabytes]);
    }

    /**
     * @return string[]
     */
    public function getEnabledModulePackageNames(): array
    {
        return $this->enabledModulePackageNames
            ?? array_values(array_diff(ModuleClassList::packageNames(), [ModulePackageList::TEST_PACKAGE]));
    }

    public function areEnabledModulePackageNamesDefined(): bool
    {
        return $this->enabledModulePackageNames !== null;
    }

    public function isModulePackageEnabled(string $modulePackageName): bool
    {
        return in_array($modulePackageName, $this->getEnabledModulePackageNames());
    }

    /**
     * @param string<class-string> $className
     */
    public function hasExtensionObject(string $className): bool
    {
        foreach ($this->extensionObjects as $extensionObject) {
            if ($extensionObject instanceof $className) {
                return true;
            }
        }

        return false;
    }

    public function withCacheDirectoryPath(string $cacheDirectoryPath): self
    {
        return new self(...[...get_object_vars($this), 'cacheDirectoryPath' => rtrim($cacheDirectoryPath, '/')]);
    }

    public function isProductionConfiguration(): bool
    {
        return in_array($this->environment, ['prod', 'production']);
    }
}
