<?php

declare(strict_types=1);

namespace Test\Ecotone\SymfonyContainer;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute\LockingInterceptor;
use Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute\OrderService;

/**
 * licence Apache-2.0
 * @internal
 */
#[RequiresPhp('>= 8.5')]
final class ClosureInAttributeTest extends TestCase
{
    public function test_intercepting_handler_with_attribute_containing_closure(): void
    {
        $lockingInterceptor = new LockingInterceptor();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [OrderService::class, LockingInterceptor::class],
            [new OrderService(), $lockingInterceptor],
        );

        $ecotoneLite->sendCommandWithRoutingKey('order.place', 'coffee');

        $this->assertSame(['coffee'], $ecotoneLite->sendQueryWithRouting('order.getOrders'));
        $this->assertSame(['order-lock'], $lockingInterceptor->getLockedResources());
    }

    public function test_intercepting_handler_with_attribute_containing_closure_using_dumped_container(): void
    {
        $cacheDirectory = sys_get_temp_dir() . '/ecotone_closure_in_attribute/' . uniqid('', true);
        $configuration = ServiceConfiguration::createWithDefaults()
            ->withCacheDirectoryPath($cacheDirectory)
            ->withSkippedModulePackageNames(ModulePackageList::allPackages());
        $lockingInterceptor = new LockingInterceptor();
        $availableServices = [
            OrderService::class => new OrderService(),
            LockingInterceptor::class => $lockingInterceptor,
        ];

        $messagingSystem = EcotoneLite::bootstrap(
            [OrderService::class, LockingInterceptor::class],
            $availableServices,
            $configuration,
            useCachedVersion: true,
        );
        $messagingSystem->getCommandBus()->sendWithRouting('order.place', 'coffee');

        $warmBootedMessagingSystem = EcotoneLite::bootstrap(
            [OrderService::class, LockingInterceptor::class],
            $availableServices,
            $configuration,
            useCachedVersion: true,
        );
        $warmBootedMessagingSystem->getCommandBus()->sendWithRouting('order.place', 'tea');

        $this->assertSame(['order-lock', 'order-lock'], $lockingInterceptor->getLockedResources());
    }
}
