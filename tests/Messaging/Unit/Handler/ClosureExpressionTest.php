<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Unit\Handler;

use Ecotone\Api\ExtensionObject\ModulePackageList;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\ExtensionObject\TestConfiguration;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Messaging\MessageHeaders;
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
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 *
 * @internal
 */
#[RequiresPhp('>= 8.5.0')]
final class ClosureExpressionTest extends TestCase
{
    public function test_header_closure_expression_resolves_parameters_like_message_handler(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ClosureExpressionService::class, UpperCaseService::class],
            [new ClosureExpressionService(), new UpperCaseService()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotoneLite->sendCommandWithRouting('notification.send', 'hello', metadata: ['token' => 'abc']);

        $this->assertSame([['hello', 'ABC']], $ecotoneLite->sendQueryWithRouting('notification.getNotifications'));
    }

    public function test_header_closure_expression_binds_header_value_to_closure_parameter_by_name(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ClosureExpressionService::class, UpperCaseService::class],
            [new ClosureExpressionService(), new UpperCaseService()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotoneLite->sendCommandWithRouting('notification.sendWithReversedToken', 'hello', metadata: ['token' => 'abc']);

        $this->assertSame([['hello', 'cba']], $ecotoneLite->sendQueryWithRouting('notification.getNotifications'));
    }

    public function test_payload_closure_expression_with_headers(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [ClosureExpressionService::class, UpperCaseService::class],
            [new ClosureExpressionService(), new UpperCaseService()],
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotoneLite->sendCommandWithRouting('order.total', ['items' => [100, 250]], metadata: ['fee' => 50]);

        $this->assertSame(400, $ecotoneLite->sendQueryWithRouting('order.getTotal'));
    }

    public function test_closure_expression_throws_licensing_exception_on_bootstrap_without_enterprise_licence(): void
    {
        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [ClosureExpressionService::class, UpperCaseService::class],
            [new ClosureExpressionService(), new UpperCaseService()],
            enabledModules: [],
        );
    }

    public function test_delayed_endpoint_attribute_with_closure_expression(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [DelayedClosureService::class],
            [new DelayedClosureService()],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('async')],
            enabledModules: [],
            testConfiguration: TestConfiguration::createWithDefaults()->withSpyOnChannel('async'),
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $headers = $ecotoneLite
            ->sendCommandWithRouting('notification.delayed', new DelayCommand(1234))
            ->popRecordedMessagesFrom('async')[0]->getHeaders()->headers();

        $this->assertSame(1234, $headers[MessageHeaders::DELIVERY_DELAY]);
    }

    public function test_delayed_closure_expression_throws_licensing_exception_on_bootstrap_without_enterprise_licence(): void
    {
        $this->expectException(LicensingException::class);

        EcotoneLite::bootstrapFlowTesting(
            [DelayedClosureService::class],
            [new DelayedClosureService()],
            extensionObjects: [SimpleMessageChannelBuilder::createQueueChannel('async')],
            enabledModules: [],
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
            enabledModules: [],
            licenceKey: LicenceTesting::VALID_LICENCE,
        );

        $ecotoneLite->sendCommandWithRouting('placeOrderWithClosureFetch', new PlaceOrder('order-1', 'user-1', 'Laptop'));

        $this->assertSame(
            ['orderId' => 'order-1', 'userId' => 'user-1', 'userName' => 'John Doe'],
            $orderService->getOrder('order-1'),
        );
    }

    public function test_closure_expression_works_with_dumped_container(): void
    {
        $cacheDirectory = sys_get_temp_dir() . '/ecotone_closure_expression/' . uniqid('', true);
        $service = new ClosureExpressionService();
        $availableServices = [
            ClosureExpressionService::class => $service,
            UpperCaseService::class => new UpperCaseService(),
        ];

        $messagingSystem = EcotoneLite::bootstrap(
            [ClosureExpressionService::class, UpperCaseService::class],
            $availableServices,
            enabledModules: [ModulePackageList::CORE_PACKAGE],
            useCachedVersion: true,
            licenceKey: LicenceTesting::VALID_LICENCE,
            cacheDirectoryPath: $cacheDirectory,
        );
        $messagingSystem->getCommandBus()->sendWithRouting('notification.send', 'first', metadata: ['token' => 'abc']);

        $warmBootedMessagingSystem = EcotoneLite::bootstrap(
            [ClosureExpressionService::class, UpperCaseService::class],
            $availableServices,
            enabledModules: [ModulePackageList::CORE_PACKAGE],
            useCachedVersion: true,
            licenceKey: LicenceTesting::VALID_LICENCE,
            cacheDirectoryPath: $cacheDirectory,
        );
        $warmBootedMessagingSystem->getCommandBus()->sendWithRouting('notification.send', 'second', metadata: ['token' => 'xyz']);

        $this->assertSame(
            [['first', 'ABC'], ['second', 'XYZ']],
            $messagingSystem->getQueryBus()->sendWithRouting('notification.getNotifications'),
        );
    }
}
