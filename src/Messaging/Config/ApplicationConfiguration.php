<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Config;


use Ecotone\Messaging\Conversion\MediaType;

class ApplicationConfiguration
{
    const DEFAULT_ENVIRONMENT = "dev";
    const DEFAULT_FAIL_FAST = true;
    const DEFAULT_LOAD_SRC = true;
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
     * @var bool
     */
    private $loadSrc = self::DEFAULT_LOAD_SRC;
    /**
     * @var string[]
     */
    private $namespaces = [];
    /**
     * @var string
     */
    private $defaultSerializationMediaType = self::DEFAULT_SERIALIZATION_MEDIA_TYPE;

    private function __construct()
    {
    }

    public static function createWithDefaults() : self
    {
        return new self();
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
     * @param bool $loadSrc
     * @return ApplicationConfiguration
     */
    public function withLoadSrc(bool $loadSrc): ApplicationConfiguration
    {
        $clone = clone $this;
        $clone->loadSrc = $loadSrc;

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

    /**
     * @return bool
     */
    public function isLoadingSrc(): bool
    {
        return $this->loadSrc;
    }

    /**
     * @return string[]
     */
    public function getNamespaces(): array
    {
        return $this->namespaces;
    }
}