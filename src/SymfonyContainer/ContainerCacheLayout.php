<?php

declare(strict_types=1);

namespace Ecotone\SymfonyContainer;

use const DIRECTORY_SEPARATOR;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\AnnotationFinder\AnnotationFinderFactory;
use Ecotone\Messaging\Config\MessagingSystemConfiguration;
use Ecotone\Messaging\Config\ServiceCacheConfiguration;
use Ecotone\Messaging\Config\ServiceConfiguration;

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
    ): self {
        $realRootCatalog = realpath($rootCatalog) ?: $rootCatalog;
        $annotationFinder = AnnotationFinderFactory::createForAttributes(
            $realRootCatalog,
            $serviceConfiguration->getNamespaces(),
            $serviceConfiguration->getEnvironment(),
            $serviceConfiguration->getLoadedCatalog() ?? '',
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

        return new self(
            $annotationFinder,
            new ServiceCacheConfiguration(
                $useHashSubDirectory ? $cacheDirectory . DIRECTORY_SEPARATOR . $configHash : $cacheDirectory,
                $shouldUseCache && ! self::containsAnonymousClass($annotationFinder->registeredClasses()),
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
