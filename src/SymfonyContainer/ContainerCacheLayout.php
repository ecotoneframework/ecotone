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
                $shouldUseCache,
            ),
            $configHash,
        );
    }
}
