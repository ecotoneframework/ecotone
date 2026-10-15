<?php

declare(strict_types=1);

namespace Test\Ecotone\SymfonyContainer;

use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\Header;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\ConfiguredMessagingSystem;
use PHPUnit\Framework\TestCase;

/**
 * licence Apache-2.0
 * @internal
 */
final class CachedBootstrapIsolationTest extends TestCase
{
    public function test_handlers_defined_as_anonymous_classes_are_not_served_from_another_bootstrap_cache(): void
    {
        $firstService = new class () {
            private array $notes = [];

            #[CommandHandler('first.store')]
            public function store(#[Header('note')] string $note): void
            {
                $this->notes[] = $note;
            }

            #[QueryHandler('first.retrieve')]
            public function retrieve(): array
            {
                return $this->notes;
            }
        };

        $secondService = new class () {
            private array $labels = [];

            #[CommandHandler('second.store')]
            public function store(#[Header('label')] string $label): void
            {
                $this->labels[] = $label;
            }

            #[QueryHandler('second.retrieve')]
            public function retrieve(): array
            {
                return $this->labels;
            }
        };

        $firstEcotone = $this->bootstrapWithCache($firstService);
        $secondEcotone = $this->bootstrapWithCache($secondService);

        $firstEcotone->getCommandBus()->sendWithRouting('first.store', metadata: ['note' => 'from first']);
        $secondEcotone->getCommandBus()->sendWithRouting('second.store', metadata: ['label' => 'from second']);

        self::assertSame(['from first'], $firstEcotone->getQueryBus()->sendWithRouting('first.retrieve'));
        self::assertSame(['from second'], $secondEcotone->getQueryBus()->sendWithRouting('second.retrieve'));
    }

    public function test_bootstraps_of_different_classes_declared_in_one_file_do_not_share_a_cached_container(): void
    {
        $cacheDirectory = sys_get_temp_dir() . '/ecotone_cached_bootstrap_isolation_' . bin2hex(random_bytes(6));

        EcotoneLite::bootstrap(
            [RegisterCustomerHandler::class],
            [new RegisterCustomerHandler()],
            ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([])->withCacheDirectoryPath($cacheDirectory),
            useCachedVersion: true,
        )->getCommandBus()->sendWithRouting('customer.register');

        $blockCustomerHandler = new BlockCustomerHandler();
        EcotoneLite::bootstrap(
            [BlockCustomerHandler::class],
            [$blockCustomerHandler],
            ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([])->withCacheDirectoryPath($cacheDirectory),
            useCachedVersion: true,
        )->getCommandBus()->sendWithRouting('customer.block');

        self::assertTrue($blockCustomerHandler->blocked);
    }

    private function bootstrapWithCache(object $service): ConfiguredMessagingSystem
    {
        return EcotoneLite::bootstrap(
            [$service::class],
            [$service],
            ServiceConfiguration::createWithDefaults()
                ->withEnabledModulePackageNames([])
                ->withCacheDirectoryPath(sys_get_temp_dir() . '/ecotone_cached_bootstrap_isolation'),
            useCachedVersion: true,
        );
    }
}

/**
 * licence Apache-2.0
 * @internal
 */
final class RegisterCustomerHandler
{
    #[CommandHandler('customer.register')]
    public function register(): void
    {
    }
}

/**
 * licence Apache-2.0
 * @internal
 */
final class BlockCustomerHandler
{
    public bool $blocked = false;

    #[CommandHandler('customer.block')]
    public function block(): void
    {
        $this->blocked = true;
    }
}
