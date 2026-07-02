<?php

declare(strict_types=1);

namespace Test\Ecotone\SymfonyContainer;

use Ecotone\AnnotationFinder\AnnotationFinder;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\SymfonyContainer\ContainerCacheLayout;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
class ContainerCacheLayoutTest extends TestCase
{
    public function test_it_resolves_annotation_finder_with_stable_config_hash_and_hash_sub_directory(): void
    {
        $serviceConfiguration = ServiceConfiguration::createWithDefaults()
            ->withSkippedModulePackageNames(ModulePackageList::allPackages());
        $cacheDirectory = sys_get_temp_dir() . '/ecotone_cache_layout_test';

        $cacheLayout = ContainerCacheLayout::resolve(
            __DIR__ . '/../../',
            $serviceConfiguration,
            $cacheDirectory,
            shouldUseCache: true,
            classesToResolve: [self::class],
        );
        $sameConfigurationLayout = ContainerCacheLayout::resolve(
            __DIR__ . '/../../',
            $serviceConfiguration,
            $cacheDirectory,
            shouldUseCache: true,
            classesToResolve: [self::class],
        );

        self::assertInstanceOf(AnnotationFinder::class, $cacheLayout->annotationFinder);
        self::assertNotEmpty($cacheLayout->configHash);
        self::assertSame($cacheLayout->configHash, $sameConfigurationLayout->configHash);
        self::assertSame($cacheDirectory . DIRECTORY_SEPARATOR . $cacheLayout->configHash, $cacheLayout->serviceCacheConfiguration->getPath());
        self::assertTrue($cacheLayout->serviceCacheConfiguration->shouldUseCache());
    }

    public function test_it_resolves_fixed_cache_directory_without_hash_sub_directory(): void
    {
        $cacheDirectory = sys_get_temp_dir() . '/ecotone_cache_layout_test_fixed';

        $cacheLayout = ContainerCacheLayout::resolve(
            __DIR__ . '/../../',
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
            $cacheDirectory,
            shouldUseCache: true,
            useHashSubDirectory: false,
            classesToResolve: [self::class],
        );

        self::assertSame($cacheDirectory, $cacheLayout->serviceCacheConfiguration->getPath());
    }
}
