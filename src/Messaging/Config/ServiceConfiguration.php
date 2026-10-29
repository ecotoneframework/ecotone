<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Apache-2.0
 */
class ServiceConfiguration
{
    public const DEFAULT_SERVICE_NAME = 'ecotoneService';
    public const DEFAULT_ENVIRONMENT              = 'dev';
    public const DEFAULT_FAIL_FAST                = true;
    public const DEFAULT_SERIALIZATION_MEDIA_TYPE = MediaType::APPLICATION_X_PHP_SERIALIZED;

    private string $serviceName = self::DEFAULT_SERVICE_NAME;
    private bool $failFast = self::DEFAULT_FAIL_FAST;
    private string $cacheDirectoryPath;
    private string $environment = self::DEFAULT_ENVIRONMENT;
    private ?string $loadCatalog = '';
    /**
     * @var string[]
     */
    private array $namespaces = [];
    /**
     * @var string[]
     */
    private array $skippedModulesPackages = [ModulePackageList::TEST_PACKAGE];
    private bool $areSkippedPackagesDefined = false;
    private ?string $defaultSerializationMediaType = null;
    private ?string $defaultErrorChannel = null;
    private ?int $defaultMemoryLimitInMegabytes = PollingMetadata::DEFAULT_MEMORY_LIMIT_MEGABYTES;
    private ?RetryTemplateBuilder $connectionRetryTemplate = null;
    /** @var object[] */
    private array $extensionObjects = [];

    /**
     * @var object[]
     */
    private array $pollableEndpointAnnotations = [];

    private ?string $licenceKey = null;

    private function __construct()
    {
        $this->cacheDirectoryPath = ServiceCacheConfiguration::defaultCachePath();
    }

    public static function createWithDefaults(): self
    {
        return (new self());
    }

    /**
     * @TODO Ecotone 2.0 make async part of core package
     */
    public static function createWithAsynchronicityOnly(): self
    {
        return self::createWithDefaults()->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::ASYNCHRONOUS_PACKAGE]));
    }

    /**
     * @param self[] $applicationConfigurations
     *
     * @return ServiceConfiguration
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

        return $self;
    }


    /**
     * @param bool $failFast
     */
    public function withFailFast(bool $failFast): self
    {
        $clone           = clone $this;
        $clone->failFast = $failFast;

        return $clone;
    }

    public function withServiceName(string $serviceName): self
    {
        if (! preg_match('#[a-z0-9_]#', $serviceName)) {
            throw ConfigurationException::create("Service name can only be used with lower case letter, numbers and underline, given: `{$serviceName}`");
        }

        $clone           = clone $this;
        $clone->serviceName = $serviceName;

        return $clone;
    }

    public function withCacheDirectoryPath(string $cacheDirectoryPath): self
    {
        $clone                     = clone $this;
        $clone->cacheDirectoryPath = rtrim($cacheDirectoryPath, '/');

        return $clone;
    }

    /**
     * @param string $environment
     */
    public function withEnvironment(string $environment): self
    {
        $clone              = clone $this;
        $clone->environment = $environment;

        return $clone;
    }

    /**
     * @param string $catalog
     */
    public function withLoadCatalog(string $catalog): self
    {
        $clone              = clone $this;
        $clone->loadCatalog = $catalog;

        return $clone;
    }

    public function withExtensionObjects(array $extensionObjects): self
    {
        foreach ($extensionObjects as $extensionObject) {
            Assert::isObject($extensionObject, 'Extension object must be an object, given: ' . gettype($extensionObject));
        }
        $clone              = clone $this;
        $clone->extensionObjects = $extensionObjects;

        return $clone;
    }

    public function addExtensionObject(object $extensionObject): self
    {
        $clone              = clone $this;
        $clone->extensionObjects[] = $extensionObject;

        return $clone;
    }

    public function doNotLoadCatalog(): self
    {
        $clone              = clone $this;
        $clone->loadCatalog = null;

        return $clone;
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
        $clone             = clone $this;
        $clone->namespaces = $namespaces;

        return $clone;
    }

    public function withDefaultSerializationMediaType(string|MediaType $defaultSerializationMediaType): self
    {
        $clone                                = clone $this;
        $clone->defaultSerializationMediaType = is_string($defaultSerializationMediaType) ? $defaultSerializationMediaType : $defaultSerializationMediaType->toString();

        return $clone;
    }

    public function withDefaultErrorChannel(string $errorChannelName): self
    {
        $clone                      = clone $this;
        $clone->defaultErrorChannel = $errorChannelName;

        return $clone;
    }

    public function withConnectionRetryTemplate(RetryTemplateBuilder $channelPollRetryTemplate): self
    {
        $this->connectionRetryTemplate = $channelPollRetryTemplate;

        return $this;
    }

    public function withLicenceKey(string $licenceKey): self
    {
        $this->licenceKey = $licenceKey;

        return $this;
    }

    public function getLicenceKey(): ?string
    {
        return $this->licenceKey;
    }

    public function isRunningForEnterprise(): bool
    {
        return $this->licenceKey !== null;
    }

    /**
     * List which packages should be skipped. The core package can't be skipped.
     * @TODO Ecotone 2.0 change to which packages should be loaded
     *
     * @link ModulePackageList list of available packages
     * @param string[] $modulePackageNames
     */
    public function withSkippedModulePackageNames(array $modulePackageNames): self
    {
        $this->skippedModulesPackages = $modulePackageNames;
        $this->areSkippedPackagesDefined = true;

        return $this;
    }

    public function getConnectionRetryTemplate(): ?RetryTemplateBuilder
    {
        return $this->connectionRetryTemplate;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    public function withConsumerMemoryLimit(int $memoryLimitInMegabytes): self
    {
        $this->defaultMemoryLimitInMegabytes = $memoryLimitInMegabytes;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getSkippedModulesPackages(): array
    {
        return $this->skippedModulesPackages;
    }

    public function areSkippedPackagesDefined(): bool
    {
        return $this->areSkippedPackagesDefined;
    }

    public function isModulePackageEnabled(string $modulePackageName): bool
    {
        return ! in_array($modulePackageName, $this->skippedModulesPackages);
    }

    /**
     * @return object[]
     */
    public function getExtensionObjects(): array
    {
        return $this->extensionObjects;
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

    public function getDefaultMemoryLimitInMegabytes(): ?int
    {
        return $this->defaultMemoryLimitInMegabytes;
    }

    /**
     * @return object[]
     */
    public function getPollableEndpointAnnotations(): array
    {
        return $this->pollableEndpointAnnotations;
    }

    /**
     * @return string|null
     */
    public function getDefaultErrorChannel(): ?string
    {
        return $this->defaultErrorChannel;
    }

    public function getDefaultSerializationMediaType(): ?string
    {
        return $this->defaultSerializationMediaType;
    }

    /**
     * @return bool
     */
    public function failFast(): bool
    {
        return $this->failFast;
    }

    /**
     * @TODO Ecotone 2.0 Rethink if it should still be here, if ServiceCacheDirectory can be used instead
     * @deprecated use ServiceCacheDirectory
     */
    public function getCacheDirectoryPath(): string
    {
        return $this->cacheDirectoryPath;
    }

    /**
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * @return bool
     */
    public function isFailingFast(): bool
    {
        return $this->failFast;
    }

    public function getLoadedCatalog(): ?string
    {
        return $this->loadCatalog;
    }

    /**
     * @return string[]
     */
    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    public function isProductionConfiguration(): bool
    {
        return in_array($this->getEnvironment(), ['prod', 'production']);
    }
}
