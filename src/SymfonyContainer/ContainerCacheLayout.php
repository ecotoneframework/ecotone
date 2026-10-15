<?php

declare(strict_types=1);

namespace Ecotone\SymfonyContainer;

use const DIRECTORY_SEPARATOR;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\AnnotationFinder\AnnotationFinderFactory;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ServiceCacheConfiguration;

use function realpath;

/**
 * licence Apache-2.0
 */
final class ContainerCacheLayout
{
    private function __construct(
        public readonly AnnotationFinder $annotationFinder,
        public readonly ServiceCacheConfiguration $serviceCacheConfiguration,
        public readonly string $configHash,
    ) {
    }

    /**
     * @param array<string, mixed> $configurationVariables
     * @param string[] $classesToResolve
     * @param string[]|null $providedServiceIds
     */
    public static function resolve(
        string $rootCatalog,
        ServiceConfiguration $serviceConfiguration,
        string $cacheDirectory,
        bool $shouldUseCache,
        bool $useHashSubDirectory = true,
        array $configurationVariables = [],
        array $classesToResolve = [],
        bool $enableTesting = false,
        ?array $providedServiceIds = [],
    ): self {
        $realRootCatalog = realpath($rootCatalog) ?: $rootCatalog;
        $annotationFinder = AnnotationFinderFactory::createForAttributes(
            $realRootCatalog,
            $serviceConfiguration->namespaces,
            $serviceConfiguration->environment,
            $serviceConfiguration->loadCatalog ?? '',
            MessagingSystemConfiguration::getModuleClassesFor($serviceConfiguration),
            $classesToResolve,
            $enableTesting,
        );
        $configHash = $annotationFinder->getCacheMessagingFileNameBasedOnConfig(
            $realRootCatalog,
            $serviceConfiguration,
            $configurationVariables,
            $enableTesting,
        );
        if ($providedServiceIds) {
            sort($providedServiceIds);
            $configHash = 'ecotone' . DIRECTORY_SEPARATOR . sha1($configHash . serialize($providedServiceIds));
        }

        return new self(
            $annotationFinder,
            new ServiceCacheConfiguration(
                $useHashSubDirectory ? $cacheDirectory . DIRECTORY_SEPARATOR . $configHash : $cacheDirectory,
                $shouldUseCache && $providedServiceIds !== null && ! self::containsAnonymousClass($annotationFinder->registeredClasses()),
            ),
            $configHash,
        );
    }

    /**
     * An anonymous class is named after the file and a counter that changes
     * between processes, so a dumped container referencing one can never be
     * resolved again. Configurations built from them are always rebuilt.
     *
     * @param class-string[] $registeredClasses
     */
    private static function containsAnonymousClass(array $registeredClasses): bool
    {
        foreach ($registeredClasses as $registeredClass) {
            if (str_contains($registeredClass, '@anonymous')) {
                return true;
            }
        }

        return false;
    }
}
