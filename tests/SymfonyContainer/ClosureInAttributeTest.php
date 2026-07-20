<?php

declare(strict_types=1);

namespace Test\Ecotone\SymfonyContainer;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute\LockingInterceptor;
use Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute\OrderService;
use Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute\PolicyDrivenTokenService;

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

    public function test_closure_expression_receiving_attribute_declared_on_handler_using_dumped_container(): void
    {
        $cacheDirectory = sys_get_temp_dir() . '/ecotone_policy_driven_closure/' . uniqid('', true);
        $configuration = ServiceConfiguration::createWithDefaults()
            ->withCacheDirectoryPath($cacheDirectory)
            ->withSkippedModulePackageNames(ModulePackageList::allPackages());
        $tokenService = new PolicyDrivenTokenService();
        $availableServices = [PolicyDrivenTokenService::class => $tokenService];

        $messagingSystem = EcotoneLite::bootstrap(
            [PolicyDrivenTokenService::class],
            $availableServices,
            $configuration,
            useCachedVersion: true,
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
        $messagingSystem->getCommandBus()->sendWithRouting('policyToken.store', metadata: ['token' => 'coffee']);

        $warmBootedMessagingSystem = EcotoneLite::bootstrap(
            [PolicyDrivenTokenService::class],
            $availableServices,
            $configuration,
            useCachedVersion: true,
            licenceKey: LicenceTesting::VALID_LICENCE,
        );
        $warmBootedMessagingSystem->getCommandBus()->sendWithRouting('policyToken.store', metadata: ['token' => 'tea']);

        $this->assertSame(
            ['COFFEE', 'TEA'],
            $tokenService->getTokens(),
            'Attribute declared on handler must be injected into closure expression on both cold and warm dumped container'
        );
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
