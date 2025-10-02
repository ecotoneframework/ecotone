<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Lite\EcotoneLite;
use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Handler\MethodInvocationException;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Modelling\AggregateNotFoundException;
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
        );

        $command = new PlaceOrder('order-123', '', 'Laptop');
        $ecotoneLite->sendCommandWithRoutingKey('placeOrderWithHeaders', $command, metadata: [
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
        );

        $command = new ComplexCommand('johny@wp.pl');
        $ecotoneLite->sendCommandWithRoutingKey('handleWithArrayIdentifiers', $command);

        $result = $complexService->getLastResult();

        $this->assertNotNull($result);
        $this->assertSame($user, $result['user']);
    }

    public function test_reference_is_not_providing_any_identifier_ending_up_with_aggregate_not_found(): void
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
        );

        // This will throw Ecotone's exception, as interface does not allow for null
        $this->expectException(MethodInvocationException::class);

        try {
            $ecotoneLite->sendCommand(new ComplexCommand('johny@wp.pl'));
            self::fail('Should throw exception');
        } catch (MethodInvocationException $e) {
            $this->assertInstanceOf(AggregateNotFoundException::class, $e->getPrevious());

            throw $e;
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

    public function test_throwing_exception_when_using_fetch_aggregate_in_non_enterprise_mode(): void
    {
        $ecotoneLite = EcotoneLite::bootstrapFlowTesting(
            [User::class, ComplexService::class, UserRepository::class],
            [
                UserRepository::class => new UserRepository([]),
                ComplexService::class => new ComplexService(),
                'identifierMapper' => new IdentifierMapper([
                    'johny@wp.pl' => 'user-1',
                ]),
            ],
        );

        $this->expectException(MethodInvocationException::class);

        try {
            $ecotoneLite->sendCommand(new ComplexCommand('johny@wp.pl'));
            self::fail('Should throw exception');
        } catch (MethodInvocationException $e) {
            $this->assertInstanceOf(LicensingException::class, $e->getPrevious());

            throw $e;
        }
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
        );
    }
}
