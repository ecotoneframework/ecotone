<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Config;


use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Conversion\MediaType;

class ApplicationConfiguration
{
    const DEFAULT_ENVIRONMENT = "dev";
    const DEFAULT_FAIL_FAST = true;
    const DEFAULT_SERIALIZATION_MEDIA_TYPE = MediaType::APPLICATION_X_PHP_SERIALIZED;

    /**
     * @var bool
     */
    private $failFast = self::DEFAULT_FAIL_FAST;
    /**
     * @var string|null
     */
    private $cacheDirectoryPath;
    /**
     * @var string
     */
    private $environment = self::DEFAULT_ENVIRONMENT;
    /**
     * @var string
     */
    private $loadCatalog = "";
    /**
     * @var string[]
     */
    private $namespaces = [];
    /**
     * @var string
     */
    private $defaultSerializationMediaType;
    /**
     * @var null|string
     */
    private $defaultErrorChannel = null;
    /**
     * @var object[]
     */
    private $pollableEndpointAnnotations = [];

    private function __construct()
    {

    }

    public static function createWithDefaults() : self
    {
        return new self();
    }

    /**
     * @param self[] $applicationConfigurations
     * @return ApplicationConfiguration
     */
    public function mergeWith(array $applicationConfigurations) : self
    {
        $self = $this;

        if (!$this->defaultSerializationMediaType) {
            $defaultSerializationMediaType = null;
            foreach ($applicationConfigurations as $applicationConfiguration) {
                if ($applicationConfiguration->defaultSerializationMediaType) {
                    if ($defaultSerializationMediaType && $applicationConfiguration->defaultSerializationMediaType !== $defaultSerializationMediaType) {
                        throw ConfigurationException::create("Ecotone can't resolve defaultSerializationMediaType. In order to continue you need to set it up.");
                    }
                    $defaultSerializationMediaType = $applicationConfiguration->defaultSerializationMediaType;
                }
            }

            $self = $self->withDefaultSerializationMediaType($defaultSerializationMediaType ?? self::DEFAULT_SERIALIZATION_MEDIA_TYPE);
        }

        if (!$this->defaultErrorChannel) {
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
     * @return ApplicationConfiguration
     */
    public function withFailFast(bool $failFast): ApplicationConfiguration
    {
        $clone = clone $this;
        $clone->failFast = $failFast;

        return $clone;
    }

    /**
     * @param string|null $cacheDirectoryPath
     * @return ApplicationConfiguration
     */
    public function withCacheDirectoryPath(?string $cacheDirectoryPath): ApplicationConfiguration
    {
        $clone = clone $this;
        $clone->cacheDirectoryPath = rtrim($cacheDirectoryPath, "/");

        return $clone;
    }

    /**
     * @param string $environment
     * @return ApplicationConfiguration
     */
    public function withEnvironment(string $environment): ApplicationConfiguration
    {
        $clone = clone $this;
        $clone->environment = $environment;

        return $clone;
    }

    /**
     * @param string $catalog
     * @return ApplicationConfiguration
     */
    public function withLoadCatalog(string $catalog): ApplicationConfiguration
    {
        $clone = clone $this;
        $clone->loadCatalog = $catalog;

        return $clone;
    }

    /**
     * @param string[] $namespaces
     * @return ApplicationConfiguration
     */
    public function withNamespaces(array $namespaces): ApplicationConfiguration
    {
        $clone = clone $this;
        $clone->namespaces = $namespaces;

        return $clone;
    }

    /**
     * @param string $defaultSerializationMediaType
     * @return ApplicationConfiguration
     */
    public function withDefaultSerializationMediaType(string $defaultSerializationMediaType): ApplicationConfiguration
    {
        $clone = clone $this;
        $clone->defaultSerializationMediaType = $defaultSerializationMediaType;

        return $clone;
    }

    public function withDefaultErrorChannel(string $errorChannelName) : ApplicationConfiguration
    {
        $clone = clone $this;
        $clone->defaultErrorChannel = $errorChannelName;

        return $clone;
    }

    /**
     * @return object[]
     */
    public function getPollableEndpointAnnotations(): array
    {
        return $this->pollableEndpointAnnotations;
    }

    /**
     * @param object[] $pollableEndpointAnnotations
     * @return ApplicationConfiguration
     */
    public function withPollableEndpointAnnotations(array $pollableEndpointAnnotations): ApplicationConfiguration
    {
        $this->pollableEndpointAnnotations = $pollableEndpointAnnotations;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getDefaultErrorChannel(): ?string
    {
        return $this->defaultErrorChannel;
    }

    /**
     * @return string
     */
    public function getDefaultSerializationMediaType(): string
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
     * @return string|null
     */
    public function getCacheDirectoryPath(): ?string
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

    public function getLoadedCatalog(): string
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
}