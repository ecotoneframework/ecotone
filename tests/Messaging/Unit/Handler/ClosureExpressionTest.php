<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Lite\Test\TestConfiguration;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Config\ModulePackageList;
use Ecotone\Messaging\Config\ServiceConfiguration;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\FetchAggregate\PlaceOrder;
use Test\Ecotone\Messaging\Fixture\FetchAggregate\User;
use Test\Ecotone\Messaging\Fixture\FetchAggregate\UserRepository;
use Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute\ClosureExpressionService;
use Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute\DelayCommand;
use Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute\DelayedClosureService;
use Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute\FetchClosureOrderService;
use Test\Ecotone\Messaging\Fixture\Handler\ClosureInAttribute\UpperCaseService;

/**
 * licence Enterprise
 * @internal
 */
#[RequiresPhp('>= 8.5')]
final class ClosureExpressionTest extends TestCase
{
    public function test_header_closure_expression_resolves_parameters_like_message_handler(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ClosureExpressionService::class, UpperCaseService::class],
            [new ClosureExpressionService(), new UpperCaseService()],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotoneLite->sendCommandWithRoutingKey('notification.send', 'hello', metadata: ['token' => 'abc']);

        $this->assertSame([['hello', 'ABC']], $ecotoneLite->sendQueryWithRouting('notification.getNotifications'));
    }

    public function test_header_closure_expression_binds_header_value_to_closure_parameter_by_name(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ClosureExpressionService::class, UpperCaseService::class],
            [new ClosureExpressionService(), new UpperCaseService()],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotoneLite->sendCommandWithRoutingKey('notification.sendWithReversedToken', 'hello', metadata: ['token' => 'abc']);

        $this->assertSame([['hello', 'cba']], $ecotoneLite->sendQueryWithRouting('notification.getNotifications'));
    }

    public function test_payload_closure_expression_with_headers(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ClosureExpressionService::class, UpperCaseService::class],
            [new ClosureExpressionService(), new UpperCaseService()],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotoneLite->sendCommandWithRoutingKey('order.total', ['items' => [100, 250]], metadata: ['fee' => 50]);

        $this->assertSame(400, $ecotoneLite->sendQueryWithRouting('order.getTotal'));
    }

    public function test_closure_expression_throws_licensing_exception_on_bootstrap_without_enterprise_licence(): void
    {
        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [ClosureExpressionService::class, UpperCaseService::class],
            [new ClosureExpressionService(), new UpperCaseService()],
        );
    }

    public function test_delayed_endpoint_attribute_with_closure_expression(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [DelayedClosureService::class],
            [new DelayedClosureService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
            testConfiguration: TestConfiguration::createWithDefaults()->withSpyOnChannel('async'),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $headers = $ecotoneLite
            ->sendCommandWithRoutingKey('notification.delayed', new DelayCommand(1234))
            ->getRecordedEcotoneMessagesFrom('async')[0]->getHeaders()->headers();

        $this->assertSame(1234, $headers[MessageHeaders::DELIVERY_DELAY]);
    }

    public function test_delayed_closure_expression_throws_licensing_exception_on_bootstrap_without_enterprise_licence(): void
    {
        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [DelayedClosureService::class],
            [new DelayedClosureService()],
            enableAsynchronousProcessing: [
                SimpleMessageChannelBuilder::createQueueChannel('async'),
            ],
        );
    }

    public function test_fetch_closure_expression_loads_aggregate(): void
    {
        $userRepository = new UserRepository([new User('user-1', 'John Doe')]);
        $orderService = new FetchClosureOrderService();
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [User::class, FetchClosureOrderService::class, UserRepository::class],
            [
                UserRepository::class => $userRepository,
                FetchClosureOrderService::class => $orderService,
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotoneLite->sendCommandWithRoutingKey('placeOrderWithClosureFetch', new PlaceOrder('order-1', 'user-1', 'Laptop'));

        $this->assertSame(
            ['orderId' => 'order-1', 'userId' => 'user-1', 'userName' => 'John Doe'],
            $orderService->getOrder('order-1'),
        );
    }

    public function test_closure_expression_works_with_dumped_container(): void
    {
        $cacheDirectory = sys_get_temp_dir() . '/ecotone_closure_expression/' . uniqid('', true);
        $configuration = ServiceConfiguration::createWithDefaults()
            ->withCacheDirectoryPath($cacheDirectory)
            ->withLicenceKey(LicenceTesting::VALID_LICENCE)
            ->withSkippedModulePackageNames(ModulePackageList::allPackagesExcept([ModulePackageList::CORE_PACKAGE]));
        $service = new ClosureExpressionService();
        $availableServices = [
            ClosureExpressionService::class => $service,
            UpperCaseService::class => new UpperCaseService(),
        ];

        $messagingSystem = EcotoneLite::bootstrap(
            [ClosureExpressionService::class, UpperCaseService::class],
            $availableServices,
            $configuration,
            useCachedVersion: true,
        );
        $messagingSystem->getCommandBus()->sendWithRouting('notification.send', 'first', metadata: ['token' => 'abc']);

        $warmBootedMessagingSystem = EcotoneLite::bootstrap(
            [ClosureExpressionService::class, UpperCaseService::class],
            $availableServices,
            $configuration,
            useCachedVersion: true,
        );
        $warmBootedMessagingSystem->getCommandBus()->sendWithRouting('notification.send', 'second', metadata: ['token' => 'xyz']);

        $this->assertSame(
            [['first', 'ABC'], ['second', 'XYZ']],
            $messagingSystem->getQueryBus()->sendWithRouting('notification.getNotifications'),
        );
    }
}
