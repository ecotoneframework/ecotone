<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Api\Attribute\Aggregate;
use Ecotone\Api\Attribute\Around;
use Ecotone\Api\Attribute\Asynchronous;
use Ecotone\Api\Attribute\Before;
use Ecotone\Api\Attribute\CommandHandler;
use Ecotone\Api\Attribute\ConsoleCommand;
use Ecotone\Api\Attribute\EventHandler;
use Ecotone\Api\Attribute\Fetch;
use Ecotone\Api\Attribute\Identifier;
use Ecotone\Api\Attribute\QueryHandler;
use Ecotone\Api\Attribute\Saga;
use Ecotone\Api\ExtensionObject\ServiceConfiguration;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;
use Ecotone\Api\Interceptor\MethodInvocation;
use Ecotone\Api\Lite\EcotoneLite;
use Ecotone\Api\Modelling\AggregateNotFoundException;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\ExpressionEvaluationException;
use Ecotone\Messaging\Handler\MethodInvocationException;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Test\LicenceTesting;
use PHPUnit\Framework\TestCase;
use Test\Ecotone\Messaging\Fixture\FetchAggregate\ComplexCommand;
use Test\Ecotone\Messaging\Fixture\FetchAggregate\ComplexService;
use Test\Ecotone\Messaging\Fixture\FetchAggregate\IdentifierMapper;
use Test\Ecotone\Messaging\Fixture\FetchAggregate\IncorrectService;
use Test\Ecotone\Messaging\Fixture\FetchAggregate\OrderService;
use Test\Ecotone\Messaging\Fixture\FetchAggregate\PlaceOrder;
use Test\Ecotone\Messaging\Fixture\FetchAggregate\User;
use Test\Ecotone\Messaging\Fixture\FetchAggregate\UserNotFound;
use Test\Ecotone\Messaging\Fixture\FetchAggregate\UserRepository;

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
class FetchAggregateTest extends TestCase
{
    public function test_fetching_aggregate_using_expression(): void
    {
        $userRepository = new UserRepository([
            new User('user-1', 'John Doe'),
        ]);
        $orderService = new OrderService();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [User::class, OrderService::class, UserRepository::class],
            [
                UserRepository::class => $userRepository,
                OrderService::class => $orderService,
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $command = new PlaceOrder('order-123', 'user-1', 'Laptop');
        $ecotoneLite->sendCommand($command);

        $order = $orderService->getOrder('order-123');

        $this->assertNotNull($order);
        $this->assertEquals('order-123', $order['orderId']);
        $this->assertEquals('user-1', $order['userId']);
        $this->assertEquals('John Doe', $order['userName']);
    }

    public function test_fetching_aggregate_using_expression_with_headers(): void
    {
        $userRepository = new UserRepository([
            new User('user-1', 'John Doe'),
        ]);
        $orderService = new OrderService();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [User::class, OrderService::class, UserRepository::class],
            [
                UserRepository::class => $userRepository,
                OrderService::class => $orderService,
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $command = new PlaceOrder('order-123', '', 'Laptop');
        $ecotoneLite->sendCommandWithRouting('placeOrderWithHeaders', $command, metadata: [
            'userId' => 'user-1',
        ]);

        $order = $orderService->getOrder('order-123');

        $this->assertNotNull($order);
        $this->assertEquals('order-123', $order['orderId']);
        $this->assertEquals('user-1', $order['userId']);
        $this->assertEquals('John Doe', $order['userName']);
    }

    public function test_fetching_aggregate_with_null_identifier_returns_null(): void
    {
        $userRepository = new UserRepository();
        $orderService = new OrderService();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [User::class, OrderService::class, UserRepository::class],
            [
                UserRepository::class => $userRepository,
                OrderService::class => $orderService,
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $command = new PlaceOrder('order-456', null, 'Mouse');

        // Interface does allow for null Aggregate, therefore application level exception will be thrown
        $this->expectException(UserNotFound::class);

        $ecotoneLite->sendCommand($command);
    }

    public function test_fetching_non_existent_aggregate_returns_null(): void
    {
        $userRepository = new UserRepository();
        $orderService = new OrderService();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [User::class, OrderService::class, UserRepository::class],
            [
                UserRepository::class => $userRepository,
                OrderService::class => $orderService,
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $command = new PlaceOrder('order-789', 'non-existent-user', 'Keyboard');

        // Interface does allow for null Aggregate, therefore application level exception will be thrown
        $this->expectException(UserNotFound::class);

        $ecotoneLite->sendCommand($command);
    }

    public function test_fetching_aggregate_with_complex_expression(): void
    {
        $userRepository = new UserRepository([
            $user = new User($userId = 'user-1', 'John Doe'),
        ]);
        $complexService = new ComplexService();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [User::class, ComplexService::class, UserRepository::class],
            [
                UserRepository::class => $userRepository,
                ComplexService::class => $complexService,
                'identifierMapper' => new IdentifierMapper(['johny@wp.pl' => $userId]),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $command = new ComplexCommand('johny@wp.pl');
        $ecotoneLite->sendCommand($command);

        $result = $complexService->getLastResult();

        $this->assertNotNull($result);
        $this->assertSame($user, $result['user']);
    }

    public function test_fetching_aggregate_with_array_of_identifiers(): void
    {
        $userRepository = new UserRepository([
            $user = new User($userId = 'user-1', 'John Doe'),
        ]);
        $complexService = new ComplexService();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [User::class, ComplexService::class, UserRepository::class],
            [
                UserRepository::class => $userRepository,
                ComplexService::class => $complexService,
                'identifierMapper' => new IdentifierMapper(['johny@wp.pl' => $userId]),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        $command = new ComplexCommand('johny@wp.pl');
        $ecotoneLite->sendCommandWithRouting('handleWithArrayIdentifiers', $command);

        $result = $complexService->getLastResult();

        $this->assertNotNull($result);
        $this->assertSame($user, $result['user']);
    }

    public function test_expression_resolving_no_identifier_names_the_attribute_the_parameter_and_the_expression(): void
    {
        $userRepository = new UserRepository([]);
        $complexService = new ComplexService();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [User::class, ComplexService::class, UserRepository::class],
            [
                UserRepository::class => $userRepository,
                ComplexService::class => $complexService,
                'identifierMapper' => new IdentifierMapper([]),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        try {
            $ecotoneLite->sendCommand(new ComplexCommand('johny@wp.pl'));
            self::fail('Should throw exception');
        } catch (MethodInvocationException $exception) {
            $expressionFailure = $exception->getPrevious();
            $this->assertInstanceOf(ExpressionEvaluationException::class, $expressionFailure);
            $this->assertSame(
                '#[Fetch] on $user in ' . ComplexService::class . '::handleComplexCommand failed.'
                . " Expression: reference('identifierMapper').map(payload.email)."
                . ' Aggregate ' . User::class . ' cannot be fetched: the expression returned null.'
                . ' Declare the parameter nullable to accept a missing identifier.',
                $expressionFailure->getMessage(),
            );
        }
    }

    public function test_reference_is_providing_identifier_yet_aggregate_is_missing_ending_up_with_aggregate_not_found(): void
    {
        $userRepository = new UserRepository([]);
        $complexService = new ComplexService();

        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [User::class, ComplexService::class, UserRepository::class],
            [
                UserRepository::class => $userRepository,
                ComplexService::class => $complexService,
                'identifierMapper' => new IdentifierMapper([
                    'johny@wp.pl' => 'user-1',
                ]),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );

        // This will throw Ecotone's exception, as interface does not allow for null
        $this->expectException(MethodInvocationException::class);

        try {
            $ecotoneLite->sendCommand(new ComplexCommand('johny@wp.pl'));
            self::fail('Should throw exception');
        } catch (MethodInvocationException $e) {
            // This will throw Ecotone's exception, as interface does not allow for null
            $this->assertInstanceOf(AggregateNotFoundException::class, $e->getPrevious());

            throw $e;
        }
    }

    public function test_refusing_fetch_on_a_command_handler_at_bootstrap_without_enterprise_licence(): void
    {
        $this->expectFetchRefusalIn(ComplexService::class . '::handleComplexCommand');

        EcotoneLite::bootstrapFlowTesting(
            [User::class, ComplexService::class, UserRepository::class],
            [
                UserRepository::class => new UserRepository([]),
                ComplexService::class => new ComplexService(),
                'identifierMapper' => new IdentifierMapper([
                    'johny@wp.pl' => 'user-1',
                ]),
            ],
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );
    }

    public function test_refusing_fetch_on_a_routed_command_handler_nothing_sends_to_at_bootstrap_without_enterprise_licence(): void
    {
        $handler = new class () {
            #[CommandHandler('order.place')]
            public function place(PlaceOrder $command, #[Fetch('payload.getUserId()')] User $user): void
            {
            }
        };

        $this->expectFetchRefusalIn($handler::class . '::place');

        EcotoneLite::bootstrapFlowTesting([User::class, $handler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }

    public function test_refusing_fetch_on_an_event_handler_at_bootstrap_without_enterprise_licence(): void
    {
        $handler = new class () {
            #[EventHandler]
            public function whenOrderPlaced(PlaceOrder $event, #[Fetch('payload.getUserId()')] User $user): void
            {
            }
        };

        $this->expectFetchRefusalIn($handler::class . '::whenOrderPlaced');

        EcotoneLite::bootstrapFlowTesting([User::class, $handler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }

    public function test_refusing_fetch_on_an_asynchronous_event_handler_at_bootstrap_without_enterprise_licence(): void
    {
        $handler = new class () {
            #[Asynchronous('async')]
            #[EventHandler(endpointId: 'notifyUser')]
            public function notifyUser(PlaceOrder $event, #[Fetch('payload.getUserId()')] User $user): void
            {
            }
        };

        $this->expectFetchRefusalIn($handler::class . '::notifyUser');

        EcotoneLite::bootstrapFlowTesting(
            [User::class, $handler::class],
            [$handler],
            ServiceConfiguration::createWithDefaults()->addExtensionObject(SimpleMessageChannelBuilder::createQueueChannel('async'))
                ->withEnabledModulePackageNames([]),
        );
    }

    public function test_refusing_fetch_on_a_query_handler_at_bootstrap_without_enterprise_licence(): void
    {
        $handler = new class () {
            #[QueryHandler('user.name')]
            public function userName(string $userId, #[Fetch('payload')] User $user): string
            {
                return $user->getName();
            }
        };

        $this->expectFetchRefusalIn($handler::class . '::userName');

        EcotoneLite::bootstrapFlowTesting([User::class, $handler::class], [$handler], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }

    public function test_refusing_fetch_on_an_aggregate_command_handler_at_bootstrap_without_enterprise_licence(): void
    {
        $aggregate = new #[Aggregate] class () {
            #[Identifier]
            public string $orderId;

            #[CommandHandler('order.assign')]
            public function assign(PlaceOrder $command, #[Fetch('payload.getUserId()')] User $user): void
            {
            }
        };

        $this->expectFetchRefusalIn($aggregate::class . '::assign');

        EcotoneLite::bootstrapFlowTesting([User::class, $aggregate::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }

    public function test_refusing_fetch_on_a_saga_event_handler_at_bootstrap_without_enterprise_licence(): void
    {
        $saga = new #[Saga] class () {
            #[Identifier]
            public string $orderId;

            #[EventHandler]
            public static function start(PlaceOrder $event, #[Fetch('payload.getUserId()')] User $user): self
            {
                $saga = new self();
                $saga->orderId = $event->getOrderId();

                return $saga;
            }
        };

        $this->expectFetchRefusalIn($saga::class . '::start');

        EcotoneLite::bootstrapFlowTesting([User::class, $saga::class], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }

    public function test_refusing_fetch_on_a_before_interceptor_at_bootstrap_without_enterprise_licence(): void
    {
        $handler = new class () {
            #[CommandHandler('order.place')]
            public function place(PlaceOrder $command): void
            {
            }
        };
        $interceptor = new class () {
            #[Before(pointcut: CommandHandler::class)]
            public function verifyUser(#[Fetch('payload.getUserId()')] User $user): void
            {
            }
        };

        $this->expectFetchRefusalIn($interceptor::class . '::verifyUser');

        EcotoneLite::bootstrapFlowTesting([User::class, $handler::class, $interceptor::class], [$handler, $interceptor], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }

    public function test_refusing_fetch_on_an_around_interceptor_at_bootstrap_without_enterprise_licence(): void
    {
        $handler = new class () {
            #[CommandHandler('order.place')]
            public function place(PlaceOrder $command): void
            {
            }
        };
        $interceptor = new class () {
            #[Around(pointcut: CommandHandler::class)]
            public function verifyUser(MethodInvocation $methodInvocation, #[Fetch('payload.getUserId()')] User $user): mixed
            {
                return $methodInvocation->proceed();
            }
        };

        $this->expectFetchRefusalIn($interceptor::class . '::verifyUser');

        EcotoneLite::bootstrapFlowTesting([User::class, $handler::class, $interceptor::class], [$handler, $interceptor], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }

    public function test_refusing_fetch_on_a_console_command_at_bootstrap_without_enterprise_licence(): void
    {
        $command = new class () {
            #[ConsoleCommand('user:show')]
            public function show(string $userId, #[Fetch("headers['ecotone.oneTimeCommand.userId']")] User $user): void
            {
            }
        };

        $this->expectFetchRefusalIn($command::class . '::show');

        EcotoneLite::bootstrapFlowTesting([User::class, $command::class], [$command], configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]));
    }

    public function test_throwing_exception_when_using_fetch_with_non_aggregate(): void
    {
        $this->expectException(ConfigurationException::class);

        EcotoneLite::bootstrapFlowTesting(
            [User::class, IncorrectService::class, UserRepository::class],
            [
                UserRepository::class => new UserRepository([]),
                IncorrectService::class => new IncorrectService(),
                'identifierMapper' => new IdentifierMapper([
                    'johny@wp.pl' => 'user-1',
                ]),
            ],
            licenceKey: LicenceTesting::VALID_LICENCE,
            configuration: ServiceConfiguration::createWithDefaults()->withEnabledModulePackageNames([]),
        );
    }

    private function expectFetchRefusalIn(string $declaringMethod): void
    {
        $this->expectException(LicensingException::class);
        $this->expectExceptionMessage(
            '#[Fetch] on $user in ' . $declaringMethod . ' is available as part of Ecotone Enterprise, and this application runs without an Enterprise licence. '
            . 'Either obtain an Enterprise licence (see https://docs.ecotone.tech/enterprise), '
            . 'or remove #[Fetch] and load ' . User::class . ' in the handler body through its repository, for example a business interface method marked with #[Repository] that returns it.'
        );
    }
}
