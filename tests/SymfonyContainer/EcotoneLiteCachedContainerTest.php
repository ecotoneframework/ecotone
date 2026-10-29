<?php

declare(strict_types=1);

namespace Test\Ecotone\SymfonyContainer;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Attribute\Parameter\Payload;
use Ecotone\Messaging\Config\ConfiguredMessagingSystem;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Modelling\Attribute\CommandHandler;
use Ecotone\Modelling\CommandBus;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Test\Ecotone\Messaging\Fixture\Annotation\MessageEndpoint\OneTimeCommand\OneTimeWithResultExample;

/**
 * licence Apache-2.0
 * @internal
 */
class EcotoneLiteCachedContainerTest extends TestCase
{
    public function test_bootstrap_with_cache_dumps_symfony_container_and_warm_boot_uses_it(): void
    {
        $cacheDirectory = sys_get_temp_dir() . '/ecotone_lite_dumped_container/' . uniqid('', true);
        $configuration = ServiceConfiguration::createWithDefaults()
            ->withCacheDirectoryPath($cacheDirectory)
            ->withSkippedModulePackageNames(ModulePackageList::allPackages());
        $handler = new CachedCommandHandlerService();

        $messagingSystem = EcotoneLite::bootstrap(
            [CachedCommandHandlerService::class],
            [CachedCommandHandlerService::class => $handler],
            $configuration,
            useCachedVersion: true,
        );
        $messagingSystem->getCommandBus()->sendWithRouting('cache.command', 'first');

        self::assertNotEmpty(glob($cacheDirectory . '/ecotone/*/ecotone_container.php'));

        $warmBootedMessagingSystem = EcotoneLite::bootstrap(
            [CachedCommandHandlerService::class],
            [CachedCommandHandlerService::class => $handler],
            $configuration,
            useCachedVersion: true,
        );
        $warmBootedMessagingSystem->getCommandBus()->sendWithRouting('cache.command', 'second');

        self::assertSame(['first', 'second'], $handler->received);
    }

    public function test_registered_console_commands_are_available_as_container_parameter(): void
    {
        $messagingSystem = EcotoneLite::bootstrap(
            [OneTimeWithResultExample::class],
            [OneTimeWithResultExample::class => new OneTimeWithResultExample()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
        );

        $container = $messagingSystem->getServiceFromContainer(ContainerInterface::class);

        $consoleCommands = $container->getRegisteredConsoleCommands();
        self::assertContains('doSomething', array_map(fn ($command) => $command->getName(), $consoleCommands));
    }

    public function test_gateway_bridges_can_be_registered_into_framework_container(): void
    {
        $messagingSystem = EcotoneLite::bootstrap(
            [CachedCommandHandlerService::class],
            [CachedCommandHandlerService::class => new CachedCommandHandlerService()],
            ServiceConfiguration::createWithDefaults()
                ->withSkippedModulePackageNames(ModulePackageList::allPackages()),
        );
        $container = $messagingSystem->getServiceFromContainer(ContainerInterface::class);

        $registeredBridges = [];
        $container->registerBridgesInto(function (string $referenceName, string $interfaceName, callable $factory) use (&$registeredBridges) {
            $registeredBridges[$referenceName] = [$interfaceName, $factory];
        });

        self::assertArrayHasKey(ConfiguredMessagingSystem::class, $registeredBridges);
        self::assertArrayHasKey(CommandBus::class, $registeredBridges);
        self::assertSame(CommandBus::class, $registeredBridges[CommandBus::class][0]);
        self::assertInstanceOf(CommandBus::class, $registeredBridges[CommandBus::class][1]());
    }
}

/**
 * licence Apache-2.0
 */
class CachedCommandHandlerService
{
    public array $received = [];

    #[CommandHandler('cache.command')]
    public function handle(#[Payload] string $payload): void
    {
        $this->received[] = $payload;
    }
}
