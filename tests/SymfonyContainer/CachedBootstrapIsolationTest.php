<?php

declare(strict_types=1);

namespace Test\Ecotone\SymfonyContainer;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\Parameter\Header;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\Attribute\QueryHandler;
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

    private function bootstrapWithCache(object $service): ConfiguredMessagingSystem
    {
        return EcotoneLite::bootstrap(
            [$service::class],
            [$service],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages())
                ->withCacheDirectoryPath(sys_get_temp_dir() . '/ecotone_cached_bootstrap_isolation'),
            useCachedVersion: true,
        );
    }
}
