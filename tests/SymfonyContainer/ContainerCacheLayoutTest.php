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

    public function test_it_resolves_different_config_hash_for_different_classes_declared_in_the_same_file(): void
    {
        $firstLayout = $this->resolveFor([FirstCacheKeyFixture::class]);
        $secondLayout = $this->resolveFor([SecondCacheKeyFixture::class]);

        self::assertNotSame($firstLayout->configHash, $secondLayout->configHash);
    }

    public function test_it_disables_cache_when_anonymous_class_is_registered(): void
    {
        $anonymousClass = new class () {
        };

        $layout = $this->resolveFor([$anonymousClass::class]);

        self::assertFalse($layout->serviceCacheConfiguration->shouldUseCache());
    }

    public function test_it_resolves_different_config_hash_when_installed_dependencies_change(): void
    {
        $rootCatalog = sys_get_temp_dir() . '/ecotone_composer_lock_test_' . bin2hex(random_bytes(6));
        mkdir($rootCatalog, 0777, true);

        try {
            file_put_contents($rootCatalog . '/composer.lock', '{"packages":[{"name":"ecotone/ecotone","version":"1.322.0"}]}');
            $beforeUpgrade = $this->resolveFor([FirstCacheKeyFixture::class], $rootCatalog);

            file_put_contents($rootCatalog . '/composer.lock', '{"packages":[{"name":"ecotone/ecotone","version":"1.323.0"}]}');
            $afterUpgrade = $this->resolveFor([FirstCacheKeyFixture::class], $rootCatalog);

            self::assertNotSame($beforeUpgrade->configHash, $afterUpgrade->configHash);
        } finally {
            @unlink($rootCatalog . '/composer.lock');
            @rmdir($rootCatalog);
        }
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

    /**
     * @param class-string[] $classesToResolve
     */
    private function resolveFor(array $classesToResolve, ?string $rootCatalog = null): ContainerCacheLayout
    {
        return ContainerCacheLayout::resolve(
            $rootCatalog ?? __DIR__ . '/../../',
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
            sys_get_temp_dir() . '/ecotone_cache_layout_test',
            shouldUseCache: true,
            classesToResolve: $classesToResolve,
        );
    }
}

/**
 * licence Apache-2.0
 */
class FirstCacheKeyFixture
{
}

/**
 * licence Apache-2.0
 */
class SecondCacheKeyFixture
{
}
