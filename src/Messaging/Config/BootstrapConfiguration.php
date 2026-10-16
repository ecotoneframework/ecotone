<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Config;

use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Apache-2.0
 */
final class BootstrapConfiguration
{
    public const DEFAULT_ENVIRONMENT = 'dev';

    /**
     * @param string[] $namespaces
     * @param string[]|null $enabledModulePackageNames
     * @param object[] $extensionObjects
     */
    private function __construct(
        public readonly string $cacheDirectoryPath,
        public readonly string $environment = self::DEFAULT_ENVIRONMENT,
        public readonly string $loadCatalog = '',
        public readonly array $namespaces = [],
        private readonly ?array $enabledModulePackageNames = null,
        public readonly ?string $licenceKey = null,
        public readonly array $extensionObjects = [],
    ) {
    }

    public static function createWithDefaults(): self
    {
        return new self(ServiceCacheConfiguration::defaultCachePath());
    }

    public function withEnvironment(string $environment): self
    {
        return new self(...[...get_object_vars($this), 'environment' => $environment]);
    }

    public function withLoadCatalog(string $catalog): self
    {
        return new self(...[...get_object_vars($this), 'loadCatalog' => $catalog]);
    }

    /**
     * @param string[] $namespaces
     */
    public function withNamespaces(array $namespaces): self
    {
        return new self(...[...get_object_vars($this), 'namespaces' => $namespaces]);
    }

    /**
     * @param string[] $enabledModulePackageNames
     */
    public function withEnabledModulePackageNames(array $enabledModulePackageNames): self
    {
        return new self(...[...get_object_vars($this), 'enabledModulePackageNames' => array_values(array_unique([...$enabledModulePackageNames, ModulePackageList::CORE_PACKAGE]))]);
    }

    public function withLicenceKey(string $licenceKey): self
    {
        return new self(...[...get_object_vars($this), 'licenceKey' => $licenceKey]);
    }

    /**
     * @param object[] $extensionObjects
     */
    public function withExtensionObjects(array $extensionObjects): self
    {
        foreach ($extensionObjects as $extensionObject) {
            Assert::isObject($extensionObject, 'Extension object must be an object, given: ' . gettype($extensionObject));
        }

        return new self(...[...get_object_vars($this), 'extensionObjects' => array_values($extensionObjects)]);
    }

    public function addExtensionObject(object $extensionObject): self
    {
        return new self(...[...get_object_vars($this), 'extensionObjects' => [...$this->extensionObjects, $extensionObject]]);
    }

    public function withCacheDirectoryPath(string $cacheDirectoryPath): self
    {
        return new self(...[...get_object_vars($this), 'cacheDirectoryPath' => rtrim($cacheDirectoryPath, '/')]);
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
        return in_array($modulePackageName, $this->getEnabledModulePackageNames(), true);
    }

    public function isRunningForEnterprise(): bool
    {
        return $this->licenceKey !== null;
    }

    public function isProductionConfiguration(): bool
    {
        return in_array($this->environment, ['prod', 'production'], true);
    }

    /**
     * @param class-string $className
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
}
