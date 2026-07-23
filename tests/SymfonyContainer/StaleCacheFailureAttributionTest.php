<?php

declare(strict_types=1);

namespace Test\Ecotone\SymfonyContainer;

use Ecotone\Lite\InMemoryPSRContainer;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\SymfonyContainer\EcotoneContainer;
use Ecotone\SymfonyContainer\ResilientDumpedContainer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Throwable;

/**
 * A production cache is trusted, never rescanned — so after a deploy removes
 * or renames a class, the cached container may still reference it. When that
 * resolution fails, the error must be attributed correctly: name the class,
 * point at the stale cache directory, and stay identical across runs — not
 * surface as a bare "Class not found" with no hint that clearing the cache
 * fixes it.
 *
 * licence Apache-2.0
 * @internal
 */
final class StaleCacheFailureAttributionTest extends TestCase
{
    private const REMOVED_CLASS = 'App\\RemovedAfterDeploy\\ReportRenderer';
    private const CACHE_PATH = '/var/cache/ecotone-test';

    public function test_class_load_failure_from_cached_container_is_attributed_to_the_stale_cache(): void
    {
        $container = $this->cachedContainerReferencingRemovedClass();

        $exception = $this->getAndCaptureFailure($container, 'stale.service');

        $this->assertInstanceOf(ConfigurationException::class, $exception);
        $this->assertStringContainsString(self::REMOVED_CLASS, $exception->getMessage(), 'The error must name the class that can no longer be loaded');
        $this->assertStringContainsString(self::CACHE_PATH, $exception->getMessage(), 'The error must point at the stale cache directory');
        $this->assertNotNull($exception->getPrevious(), 'The original load failure must be preserved');
    }

    public function test_stale_cache_failure_is_identical_across_repeated_resolutions(): void
    {
        $container = $this->cachedContainerReferencingRemovedClass();

        $firstException = $this->getAndCaptureFailure($container, 'stale.service');
        $secondException = $this->getAndCaptureFailure($container, 'stale.service');

        $this->assertSame($firstException::class, $secondException::class);
        $this->assertSame(
            $firstException->getMessage(),
            $secondException->getMessage(),
            'A failed resolution must not leave state behind that changes the error on the next attempt',
        );
    }

    public function test_class_load_failure_without_cache_is_not_blamed_on_a_cache(): void
    {
        $container = $this->containerReferencingRemovedClass(loadedFromCachePath: null);

        $exception = $this->getAndCaptureFailure($container, 'stale.service');

        $this->assertNotInstanceOf(ConfigurationException::class, $exception, 'Without a cache in play the original error must pass through untouched');
    }

    private function getAndCaptureFailure(EcotoneContainer $container, string $id): Throwable
    {
        try {
            $container->get($id);
        } catch (Throwable $exception) {
            return $exception;
        }

        $this->fail('Resolution was expected to fail');
    }

    private function cachedContainerReferencingRemovedClass(): EcotoneContainer
    {
        return $this->containerReferencingRemovedClass(self::CACHE_PATH);
    }

    private function containerReferencingRemovedClass(?string $loadedFromCachePath): EcotoneContainer
    {
        $builder = new ContainerBuilder();
        $builder->register('stale.service', self::REMOVED_CLASS)->setPublic(true);
        $builder->compile();

        $className = 'StaleCacheDumpedContainer_' . md5(uniqid('', true));
        $code = (new PhpDumper($builder))->dump([
            'class' => $className,
            'base_class' => '\\' . ResilientDumpedContainer::class,
        ]);

        eval(substr($code, strlen('<?php')));

        return new EcotoneContainer(
            new $className(),
            InMemoryPSRContainer::createEmpty(),
            $loadedFromCachePath,
        );
    }
}
